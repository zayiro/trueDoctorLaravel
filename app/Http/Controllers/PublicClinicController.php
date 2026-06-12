<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Address;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Specialty;

class PublicClinicController extends Controller
{
    /**
     * Renderiza la pantalla intermedia pública de decisión para la clínica seleccionada.
     */
    public function showClinicStaff(Request $request, $slug, $specialtySlug = null)
    {
        // 1. Contexto de tiempo exacto del servidor (Regla de Oro de opendoctor.online)
        $now = Carbon::now();
        $currentTime = $now->toTimeString();

        // 2. Recuperar la clínica corporativa validando que esté aprobada y activa
        $clinic = Clinic::with(['user', 'specialties'])
            ->where('slug', $slug)
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->firstOrFail();

        // 3. Detección Inteligente Bimodal con Bandera de Fallback Integrada
        $specialty = null;
        $showingAllStaffFallback = false;

        if ($specialtySlug || $request->has('specialty')) {
            $specialtyInput = $specialtySlug ?? $request->input('specialty');
            $specialty = Specialty::where('slug', $specialtyInput)->orWhere('id', $specialtyInput)->first();
        }

        // Si no se solicitó especialidad, o la solicitada no existe, activamos el fallback global
        if (!$specialty) {
            $showingAllStaffFallback = true;
        }

        // 4. FILTRADO MAESTRO SEGURO (Aislado para Multi-Tenancy y Fallback)
        $doctorsQuery = $clinic->doctors()
            ->where('clinic_doctor.status', 'approved')
            ->where('doctors.validation_status', 'approved')
            ->with(['user', 'specialties']);

        // Aplicar filtro de especialidad solo si no estamos en modo "traer todas"
        if (!$showingAllStaffFallback) {
            // Validamos primero si existen médicos para esa combinación específica
            $hasDoctorsInSpecialty = (clone $doctorsQuery)->whereHas('specialties', function ($query) use ($specialty) {
                $query->where('specialties.id', $specialty->id);
            })->exists();

            if ($hasDoctorsInSpecialty) {
                $doctorsQuery->whereHas('specialties', function ($query) use ($specialty) {
                    $query->where('specialties.id', $specialty->id);
                });
            } else {
                // Si se solicitó una especialidad pero la clínica no tiene médicos en ella, forzamos fallback global
                $showingAllStaffFallback = true;
            }
        }

        $doctors = $doctorsQuery->get();

        // 5. OBTENER INFRAESTRUCTURA DE LA CLÍNICA Y AGENDAS ASOCIADAS
        $clinicAddresses = $clinic->addresses()->where('status', true)->get();
        $clinicAddressIds = $clinicAddresses->pluck('id')->toArray();
        $staffDoctorIds = $doctors->pluck('id')->toArray();

        // Cargar agendas activas de este grupo de médicos exclusivamente en las sedes de esta clínica
        $schedules = Schedule::whereIn('doctor_id', $staffDoctorIds)
            ->whereIn('address_id', $clinicAddressIds)
            ->get();

        // 6. SÍNTESIS DE DATOS HÍBRIDA (Garantiza visibilidad del médico aunque no tenga agendas cargadas)
        $results = [];

        foreach ($doctors as $doctor) {
            // Extraer las sedes físicas de la clínica donde este médico específico tiene horarios registrados
            $activeAddressIds = $schedules->where('doctor_id', $doctor->id)
                ->pluck('address_id')->unique()->values()->toArray();

            // RESOLUCIÓN DE SEDE PRINCIPAL (Cero regresión: Si no tiene agenda, le asignamos la primera sede de la clínica)
            if (!empty($activeAddressIds)) {
                $primaryAddressId = $activeAddressIds[0];
                $addressModel = $clinicAddresses->firstWhere('id', $primaryAddressId);
                
                // Llamada al motor de inmediatez nativo si registra turnos
                $nextAvailableTurn = $this->calculateNextTurn($primaryAddressId, [$doctor->id], $now, $currentTime);
            } else {
                // El médico no tiene turnos configurados aún, pero DEBE mostrarse en el staff institucional
                $addressModel = $clinicAddresses->first(); // Sede por defecto de la institución
                $primaryAddressId = $addressModel ? $addressModel->id : null;
                $nextAvailableTurn = 'Sin turnos asignados esta semana';
            }

            // Determinar el badge de especialidad dinámico por fila (Garantiza consistencia en modo "Traer Todas")
            if (!$showingAllStaffFallback && $specialty) {
                $doctorBadgeText = $specialty->name;
            } else {
                // Si estamos mostrando todo el staff, toma la primera especialidad registrada del médico
                $doctorBadgeText = $doctor->specialties->first()->name ?? 'Medicina General';
            }

            // Inyectar el arreglo molecular sanitizado
            $results[] = [
                'id'                 => $doctor->id,
                'slug'               => $doctor->slug ?? $doctor->user->slug,
                'type'               => 'doctor',
                'title'              => ($doctor->gender === 'female' ? 'Dra. ' : 'Dr. ') . ucfirst($doctor->user->name),
                'subtitle'           => $addressModel ? "Consultorio: {$addressModel->name} • {$addressModel->address_line}" : $clinic->brand_name,
                'rating'             => $doctor->rating ?? 5,
                'address_id'         => $primaryAddressId,
                'active_address_ids' => $activeAddressIds,
                'badge_text'         => $doctorBadgeText,
                'next_turn'          => $nextAvailableTurn,
                'user'               => $doctor->user
            ];
        }       
        
        return view('public.clinic_decision', compact(
            'clinic', 
            'specialty', 
            'results', 
            'clinicAddresses', 
            'showingAllStaffFallback'
        ));
    }

    /**
     * Motor nativo de inmediatez del buscador principal para calcular turnos (Depurado y Blindado)
     */
    private function calculateNextTurn($addressId, array $doctorIds, $now, $currentTime)
    {
        // Traer horarios semanales configurados para esta combinación exacta
        $schedules = Schedule::whereIn('doctor_id', $doctorIds)
            ->where('address_id', $addressId)
            ->orderBy('start_time', 'asc')
            ->get();

        // Evaluar disponibilidad secuencial en los próximos 7 días calendarios
        for ($i = 0; $i < 7; $i++) {
            $currentDate = $now->copy()->addDays($i);
            $dayOfWeek = $currentDate->dayOfWeekIso; // 1 (Lunes) a 7 (Domingo)

            $match = $schedules->where('day', $dayOfWeek)->first();
            
            if ($match) {
                // Si la coincidencia es hoy mismo, validar que la hora del turno no haya pasado ya
                if ($i === 0 && $match->start_time <= $currentTime) {
                    continue;
                }

                $timeHuman = Carbon::parse($match->start_time)->format('g:i A');
                $dateHuman = $currentDate->isToday() ? 'Hoy' : ($currentDate->isTomorrow() ? 'Mañana' : $currentDate->translatedFormat('l d'));
                
                return "{$dateHuman} a las {$timeHuman}";
            }
        }
        
        return null;
    }
}
