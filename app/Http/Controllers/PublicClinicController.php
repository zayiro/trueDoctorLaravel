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
        $clinic = Clinic::with(['user'])
            ->where('slug', $slug)
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->firstOrFail();

        // 3. Detección Inteligente Bimodal de la Especialidad Requerida
        $specialtyInput = $specialtySlug ?? $request->input('specialty');
        $specialty = Specialty::where('slug', $specialtyInput)->orWhere('id', $specialtyInput)->first();

        // Fallback preventivo si el parámetro viene vacío para no romper la navegación
        if (!$specialty) {
            $specialty = $clinic->specialties()->first() ?? (object) [
                'id' => 1, 'name' => 'Consulta General', 'slug' => 'general'
            ];
        }

        // 4. FILTRADO MAESTRO SEGURO: Obtener médicos aprobados vinculados a la clínica y a la especialidad
        $doctors = $clinic->doctors()
            ->where('clinic_doctor.status', 'approved')
            ->whereHas('specialties', function ($query) use ($specialty) {
                $query->where('specialty_id', $specialty->id);
            })
            ->with(['user', 'specialties'])
            ->get();

        // 5. OBTENER INFRAESTRUCTURA DE LA CLÍNICA Y AGENDAS ASOCIADAS
        $clinicAddresses = $clinic->addresses()->where('status', true)->get();
        $clinicAddressIds = $clinicAddresses->pluck('id')->toArray();
        $staffDoctorIds = $doctors->pluck('id')->toArray();

        // Cargar agendas activas de este grupo de médicos exclusivamente en las sedes de esta clínica
        $schedules = Schedule::whereIn('doctor_id', $staffDoctorIds)
            ->whereIn('address_id', $clinicAddressIds)
            ->get();

        // 6. SÍNTESIS DE DATOS HÍBRIDA (Mapeo molecular exacto para las tarjetas del index)
        $results = [];

        foreach ($doctors as $doctor) {
            // Extraer las sedes físicas de la clínica donde este médico específico tiene horarios registrados
            $activeAddressIds = $schedules->where('doctor_id', $doctor->id)
                ->pluck('address_id')->unique()->values()->toArray();

            // Si el médico pertenece a la clínica pero no atiende esta especialidad en ninguna sede, se ignora
            if (empty($activeAddressIds)) {
                continue;
            }

            // Seleccionar la sede principal (La primera donde registre agenda)
            $primaryAddressId = $activeAddressIds[0];
            $addressModel = $clinicAddresses->firstWhere('id', $primaryAddressId);

            // 🔥 LLAMADA DEPURADA AL MOTOR DE INMEDIATEZ NATIVO DE OPENDOCTOR
            // Corregido: Pasamos el ID de la sede correcto y el ID del doctor dentro de un array compatible
            $nextAvailableTurn = $this->calculateNextTurn($primaryAddressId, [$doctor->id], $now, $currentTime);

            // Determinar el badge de especialidad (Fiel a la regla de tu else original)
            $doctorBadgeText = $specialty ? $specialty->name : ($doctor->specialties->first()->name ?? 'Consultorio Privado');

            // Inyectar el arreglo molecular sanitizado
            $results[] = [
                'id'                 => $doctor->id,
                'slug'               => $doctor->slug ?? $doctor->user->slug,
                'type'               => 'doctor',
                // Manejo de prefijo de género estricto según tu regla de negocio original
                'title'              => ($doctor->gender === 'female' ? 'Dra. ' : 'Dr. ') . ucfirst($doctor->user->name),
                'subtitle'           => $addressModel ? "Consultorio: {$addressModel->name} • {$addressModel->address}" : $clinic->brand_name,
                'rating'             => $doctor->rating ?? 5,
                'address_id'         => $primaryAddressId,
                'active_address_ids' => $activeAddressIds,
                'badge_text'         => $doctorBadgeText,
                'next_turn'          => $nextAvailableTurn,
                'user'               => $doctor->user
            ];
        }       

        return view('public.clinic_decision', compact('clinic', 'specialty', 'results', 'clinicAddresses'));
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
