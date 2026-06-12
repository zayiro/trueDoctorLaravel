<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\Service;
use App\Models\Address;
use App\Models\Specialty;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmed;
use App\Notifications\NewAppointmentNotification;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PublicProfileController extends Controller
{   
    /**
     * Muestra la pantalla del perfil unificado (Clínica o Doctor de opendoctor.online).
     * URL: /medical-partner/{slug}
     */
    public function show(Request $request, $slug)
    {        
        $addressId = $request->input('address_id');
        $now = Carbon::now();
        $currentTime = $now->toTimeString();

        // 🔒 CAPTURA MAESTRA DE LA ESPECIALIDAD REQUERIDA
        $specialtyInput = $request->input('specialty');
        $currentSpecialty = Specialty::where('status', true)
            ->where(function($query) use ($specialtyInput) {
                $query->where('slug', $specialtyInput)->orWhere('id', $specialtyInput);
            })->first();

        // --------------------------------------------------------------------
        // 1. INTENTAR BUSCAR EN EL CATÁLOGO MAESTRO DE CLÍNICAS APROBADAS
        // --------------------------------------------------------------------
        $clinic = Clinic::where('slug', $slug)
            ->where('validation_status', 'approved')
            ->where('active', true)
            ->first();
        if ($clinic) {
            $showingAllStaffFallback = false;

            // Determinar si la especialidad solicitada tiene médicos activos en la clínica
            if ($currentSpecialty) {
                $hasDoctorsInSpecialty = $clinic->doctors()
                    ->where('clinic_doctor.status', 'approved')
                    ->where('doctors.validation_status', 'approved')
                    ->whereHas('specialties', function($sp) use ($currentSpecialty) {
                        $sp->where('specialty_id', $currentSpecialty->id);
                    })->exists();

                if (!$hasDoctorsInSpecialty) {
                    $showingAllStaffFallback = true;
                }
            } else {
                $showingAllStaffFallback = true;
            }

            // Cargar relaciones aplicando el Fallback de Especialidades de la clínica
            $clinic->load([
                'user',
                'doctors' => function ($query) use ($currentSpecialty, $showingAllStaffFallback) {
                    $query->where('clinic_doctor.status', 'approved')
                          ->where('doctors.validation_status', 'approved')
                          ->when(!$showingAllStaffFallback, function($q) use ($currentSpecialty) {
                              $q->whereHas('specialties', function($sp) use ($currentSpecialty) {
                                  $sp->where('specialty_id', $currentSpecialty->id);
                              });
                          })
                          ->with(['user', 'specialties']);
                },
                'addresses' => function ($query) use ($addressId) {
                    $query->where('status', true)->with(['city', 'services' => fn($srv) => $srv->where('services.active', true)]);
                    if ($addressId) {
                        $query->where('id', $addressId);
                    }
                }
            ]);

            // Determinar la sede física de trabajo evaluada
            $address = $addressId 
                ? $clinic->addresses->where('id', $addressId)->first() 
                : $clinic->addresses->first();

            if (!$address) {
                abort(404, 'The requested medical address is not available.');
            }

            if ($showingAllStaffFallback && $clinic->doctors->isNotEmpty()) {
                $currentSpecialty = $clinic->doctors->first()->specialties->first();
            }
            // Algoritmo nativo de inmediatez del staff de la clínica (Corregido y Optimizado)
            $doctorIds = $clinic->doctors()->pluck('doctors.id')->toArray();
            $unifiedSlots = [];

            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                $evalDate = Carbon::now()->addDays($dayOffset);
                $evalDayOfWeek = $evalDate->dayOfWeekIso;

                $schedules = DB::table('schedules')
                    ->where('address_id', $address->id)
                    ->where('day', $evalDayOfWeek)
                    ->whereIn('doctor_id', $doctorIds)
                    ->get();

                foreach ($schedules as $sched) {
                    if ($dayOffset === 0 && $sched->start_time < $currentTime) {
                        continue;
                    }

                    // Validación Transaccional Fiel: El turno pertenece estrictamente al médico asignado
                    $isBooked = Appointment::where('address_id', $address->id)
                        ->where('doctor_id', $sched->doctor_id)
                        ->where('date', $evalDate->toDateString())
                        ->where('start_time', $sched->start_time)
                        ->whereIn('status', ['pending', 'confirmed', 'completed'])
                        ->exists();

                    if (!$isBooked) {
                        $targetDoctor = $clinic->doctors->firstWhere('id', $sched->doctor_id);

                        $unifiedSlots[] = [
                            'date'        => $evalDate->toDateString(),
                            'date_human'  => $evalDate->translatedFormat('D d \d\e F'),
                            'start_time'  => $sched->start_time,
                            'time_human'  => Carbon::parse($sched->start_time)->format('g:i A'),
                            'doctor_id'   => $sched->doctor_id,
                            'doctor_name' => $targetDoctor ? $targetDoctor->user->name : 'Especialista'
                        ];

                        if (count($unifiedSlots) >= 24) break 2;
                    }
                }
            }
            
                        // FÓRMULA DE SÍNTESIS DEPURADA Y BLINDADA: Prepara las tarjetas moleculares para la clínica
            $results = [];
            $clinicAddresses = $clinic->addresses;

            foreach ($clinic->doctors as $doc) {
                // Buscamos en qué sedes de ESTA clínica tiene horarios reales configurados este médico
                $activeAddressIds = DB::table('schedules')
                    ->where('doctor_id', $doc->id)
                    ->whereIn('address_id', $clinicAddresses->pluck('id'))
                    ->pluck('address_id')
                    ->unique()
                    ->toArray();
                
                // 🔥 CORRECCIÓN CRÍTICA: Extraemos el primer ID numérico del array. 
                // Si el array está vacío, usamos el ID real de la sede actual evaluada ($address->id)
                $assignedAddressId = !empty($activeAddressIds) ? $activeAddressIds[0] : $address->id;
                
                $targetAddressModel = $clinicAddresses->firstWhere('id', $assignedAddressId) ?? $address;

                $results[] = [
                    'id'                 => $doc->id,
                    'slug'               => $doc->slug ?? $doc->user->slug,
                    'type'               => 'doctor',
                    'title'              => ($doc->gender === 'female' ? 'Dra. ' : 'Dr. ') . ucfirst($doc->user->name),
                    'subtitle'           => "Sede " . $targetAddressModel->name . " • " . $targetAddressModel->address_line,
                    'rating'             => $doc->rating ?? 5,
                    
                    // 🔒 AHORA SÍ: Envía un entero limpio (ej: 8 o 10) al parámetro address_id de la URL de Blade
                    'address_id'         => (int)$assignedAddressId, 
                    'active_address_ids' => !empty($activeAddressIds) ? $activeAddressIds : [$address->id],
                    'badge_text'         => $doc->specialties->first()->name ?? ($currentSpecialty ? $currentSpecialty->name : 'Especialista'),
                    'next_turn'          => collect($unifiedSlots)->firstWhere('doctor_id', $doc->id)['time_human'] ?? 'Sin turnos esta semana',
                    'user'               => $doc->user
                ];
            }

            session(['current_clinic_user_id' => $clinic->user_id]);
            session()->forget('current_doctor_id');

            return view('public.profiles.clinic_decision', [
                'clinic'                  => $clinic,
                'specialty'               => $currentSpecialty,
                'clinicAddresses'         => $clinicAddresses,
                'results'                 => $results,
                'address'                 => $address,
                'unifiedSlots'            => $unifiedSlots,
                'showingAllStaffFallback' => $showingAllStaffFallback,
                'seoTitle'                => $clinic->name . ' | Selección de Turno',
                'seoDescription'          => $clinic->bio ?? 'Elige entre atención inmediata o tu especialista preferido.'
            ]);
        }
        // --------------------------------------------------------------------
        // 2. SI NO ES UNA CLÍNICA, BUSCAMOS EN EL CATÁLOGO DE ESPECIALISTAS INDEPENDIENTES
        // --------------------------------------------------------------------
        $doctor = Doctor::where('slug', $slug)
            ->where('validation_status', 'approved')
            ->where('active', true)
            ->firstOrFail();

        $fromClinicId = $request->input('from_clinic');
        $preSelectedAddress = null;

        // LÓGICA DE PRODUCCIÓN ORIGINAL RESTAURADA AL 100%
        $doctor->load([
            'user',
            'specialties' => function ($query) use ($currentSpecialty) {
                if ($currentSpecialty && isset($currentSpecialty->id)) {
                    $query->orderByRaw('CASE WHEN specialties.id = ? THEN 0 ELSE 1 END', [$currentSpecialty->id])
                          ->orderBy('name', 'asc');
                } else {
                    $query->orderBy('name', 'asc');
                }
            },
            'addresses' => function ($query) {
                $query->where('status', true)->with(['city', 'services']);
            }
        ]);

        if (!$currentSpecialty) {
            $currentSpecialty = $doctor->specialties->first() ?? (object) [
                'id' => 1, 'name' => 'Consulta General', 'slug' => 'general'
            ];
        }

                // 🔒 AISLAMIENTO MULTI-TENANT CONTEXTUAL (REPARACIÓN DE ATENCIÓN VIRTUAL)
        if ($fromClinicId) {
            $parentClinic = Clinic::where('id', $fromClinicId)
                ->where('validation_status', 'approved')
                ->first();

            // Mantenemos la carga original de direcciones del médico (admite clinic_id = null si es su dirección virtual)
            // para que la sede virtual autogenerada por el Observer no sea eliminada por la consulta SQL.
            $doctor->load(['addresses' => function ($query) {
                $query->where('status', true)->with(['city', 'services' => fn($q) => $q->where('services.active', true)]);
            }]);
        }


        if ($addressId && $fromClinicId) {
            $preSelectedAddress = $doctor->addresses->where('id', $addressId)->first();
        }

        session(['current_doctor_id' => $doctor->user_id]);
        session()->forget('current_clinic_user_id');

        return view('public.public-profile', [
            'partner'            => $doctor,
            'profileType'        => 'doctor',
            'currentSpecialty'   => $currentSpecialty,
            'seoTitle'           => "Dr(a). " . ucfirst($doctor->user->name) . ' | Agendamiento en Línea',
            'seoDescription'     => $doctor->bio ?? 'Especialista profesional calificado, agenda tu cita médica en línea.',
            'metaRobots'         => 'index, follow',
            'preSelectedAddress' => $preSelectedAddress,
            'fromClinicId'       => $fromClinicId
        ]);
    }        
}
