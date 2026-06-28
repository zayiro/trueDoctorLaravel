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
use App\Services\ColombiaHolidayService;

class PublicProfileController extends Controller
{          
    /**
     * Muestra la pantalla del perfil unificado (Clínica o Doctor de opendoctor.online).
     * URL: /medical-partner/{slug}
     */
    public function show__(Request $request, $slug)
    {
        $addressId = $request->input('address_id');
        $now = Carbon::now();
        $currentTime = $now->toTimeString();

        // 🔒 CAPTURA MAESTRA DE LA ESPECIALIDAD REQUERIDA (Soporta Slug e ID numérico)
        $targetSpecialtySlug = $request->input('specialty');
        
        $currentSpecialty = Specialty::where('status', true)
            ->where(function($query) use ($targetSpecialtySlug) {
                $query->where('slug', $targetSpecialtySlug)->orWhere('id', $targetSpecialtySlug);
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

            // Algoritmo nativo de inmediatez del staff de la clínica
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
                
                // Extraemos el primer ID numérico del array. Fallback de la sede actual evaluada ($address->id)
                $assignedAddressId = !empty($activeAddressIds) ? $activeAddressIds[0] : $address->id;
                
                $targetAddressModel = $clinicAddresses->firstWhere('id', $assignedAddressId) ?? $address;

                $results[] = [
                    'id'                 => $doc->id,
                    'slug'               => $doc->slug ?? $doc->user->slug,
                    'type'               => 'doctor',
                    'title'              => ($doc->gender === 'female' ? 'Dra. ' : 'Dr. ') . ucfirst($doc->user->name),
                    'subtitle'           => "Sede " . $targetAddressModel->name . " • " . $targetAddressModel->address,
                    'rating'             => $doc->rating ?? 5,
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

        $fromClinicId = $request->input('from_clinic') ?? $request->input('clinic_id');
        $preSelectedAddress = null;

        // Carga base unificada de datos del médico y sus especialidades ordenadas
        $doctor->load([
            'user',
            'specialties' => function ($query) use ($targetSpecialtySlug) {
                if ($targetSpecialtySlug && isset($targetSpecialtySlug)) {
                    $query->orderByRaw('CASE WHEN specialties.id = ? THEN 0 ELSE 1 END', [$targetSpecialtySlug])
                          ->orderBy('name', 'asc');
                } else {
                    $query->orderBy('name', 'asc');
                }
            }
        ]);

        if (!$currentSpecialty) {
            $currentSpecialty = $doctor->specialties->first() ?? (object) [
                'id' => 1, 'name' => 'Consulta General', 'slug' => 'general'
            ];
        }

        // =========================================================================
        // 🏢 CASO 1: EL MÉDICO ESTÁ OPERANDO COMO STAFF DE UNA CLÍNICA (?from_clinic=1)
        // =========================================================================
        if ($fromClinicId) {
            $parentClinic = Clinic::where('id', $fromClinicId)
                ->where('validation_status', 'approved')
                ->first();

            if (!$parentClinic) {
                abort(404, 'La institución médica vinculada no se encuentra activa.');
            }

            // 1. 🔒 FILTRADO ESTRICTO: Traer únicamente las sedes oficiales de la clínica
            $clinicAddresses = Address::where('status', true)
                ->where('clinic_id', $fromClinicId)
                ->get();

            // 2. 🧬 DETECCIÓN HÍBRIDA MULTI-TENANT: Iteramos cada sede para inyectarle sus servicios institucionales
            foreach ($clinicAddresses as $currentAddr) {
                
                // 🛡️ CONTROL DE MODALIDAD FIEL A MARIADB:
                // Si la sede es VIRTUAL, jalamos directo de address_service para evitar cuellos de botella
                if ($currentAddr->type === 'virtual') {
                    $rawServices = DB::table('address_service')
                        ->join('services', 'address_service.service_id', '=', 'services.id')
                        ->where('address_service.address_id', $currentAddr->id)
                        ->where('services.active', true)
                        ->select('services.id', 'services.name', 'address_service.price', 'address_service.duration')
                        ->distinct()
                        ->get();
                } 
                // Si la sede es FÍSICA, mantenemos el filtro cruzado por especialidad médica
                else {
                    $rawServices = DB::table('address_service')
                        ->join('services', 'address_service.service_id', '=', 'services.id')
                        ->join('service_specialty', 'services.id', '=', 'service_specialty.service_id')
                        ->join('doctor_specialty', 'service_specialty.specialty_id', '=', 'doctor_specialty.specialty_id')
                        ->where('address_service.address_id', $currentAddr->id)
                        ->where('services.active', true)
                        ->where('doctor_specialty.doctor_id', $doctor->id)
                        ->when($targetSpecialtySlug, function($q) use ($targetSpecialtySlug) {
                            $q->join('specialties', 'service_specialty.specialty_id', '=', 'specialties.id')
                              ->where(function($sub) use ($targetSpecialtySlug) {
                                  $sub->where('specialties.slug', $targetSpecialtySlug)
                                      ->orWhere('specialties.id', $targetSpecialtySlug);
                              });
                        })
                        ->select('services.id', 'services.name', 'address_service.price', 'address_service.duration')
                        ->distinct()
                        ->get();
                }

                // Formateamos los servicios inyectando el objeto pivot simétrico que espera el frontend
                $formattedServices = $rawServices->map(function($srv) {
                    $srv->pivot = (object)[
                        'price' => $srv->price,
                        'duration' => $srv->duration
                    ];
                    return $srv;
                });

                // Asignación manual e inmune de la relación en memoria ram
                $currentAddr->setRelation('services', $formattedServices);
            }

            // Sobreescribe la colección del médico con el staff corporativo blindado
            $doctor->setRelation('addresses', $clinicAddresses);

            session(['current_clinic_user_id' => $parentClinic->user_id]);
            session(['current_doctor_id' => $doctor->user_id]);
        }

                // =========================================================================
        // 🩺 CASO 2: EL MÉDICO OPERA EN SU CONSULTORIO PARTICULAR AUTÓNOMO
        // =========================================================================
        else {
            // Lógica original de producción: el médico es dueño de sus propios servicios y tarifas
            $doctor->load(['addresses' => function ($query) use ($doctor) {
                $query->where('status', true)
                    ->whereNull('clinic_id') // Solo consultorios privados
                    ->with(['city', 'services' => function($query) use ($doctor) {
                        $query->where('services.active', true)
                            ->withPivot('price', 'duration') // Extrae tarifas particulares de address_service
                            ->whereIn('services.id', function($subQuery) use ($doctor) {
                                $subQuery->select('service_specialty.service_id')
                                    ->from('service_specialty')
                                    ->where('service_specialty.user_id', $doctor->user_id); // Catálogo del Médico Individual
                            });
                    }]);
            }]);

            session(['current_doctor_id' => $doctor->user_id]);
            session()->forget('current_clinic_user_id');
        }

        // =========================================================================
        // 🔒 ALGORITMO HÍBRIDO AVANZADO: RECOPILACIÓN DE FECHAS CON SLOTS REALES
        // =========================================================================
        $nextAvailableDate = Carbon::now()->toDateString();
        $targetAddressId = null;
        $enabledDates = []; // Array que alimentará a Flatpickr en el frontend

        // Determinar de forma segura qué address_id evaluar para el cronograma predictivo
        if ($addressId) {
            $targetAddressId = $addressId;
        } elseif ($doctor->addresses->isNotEmpty()) {
            $targetAddressId = $doctor->addresses->first()->id;
        }

        if ($targetAddressId) {
            // 🛡️ Cargar las configuraciones de citas del doctor o usar fallbacks de producción
            $settings = DB::table('doctor_settings')->where('doctor_id', $doctor->id)->first();
            $minNoticeHours = $settings->min_notice_hours ?? 2;
            $maxAdvanceDays = $settings->max_advance_days ?? 30;

            $now = Carbon::now();
            $maxDate = Carbon::now()->addDays($maxAdvanceDays);

            // 🔒 RESTRICCIÓN DESACOPLADA PREMIUM: Consumo del servicio centralizado
            $colombianHolidays = ColombiaHolidayService::getHolidays($now->year);

            // 1. Traer todos los horarios configurados por el médico en esta sede
            $baseSchedules = DB::table('schedules')
                ->where('address_id', $targetAddressId)
                ->where('doctor_id', $doctor->id)
                ->get()
                ->groupBy('day'); // Agrupamos por día de la semana (1 = Lunes, 7 = Domingo)

            // 2. Traer todas las citas activas/reservadas en este rango de días para optimizar memoria
            $bookedAppointments = Appointment::where('address_id', $targetAddressId)
                ->where('doctor_id', $doctor->id)
                ->whereBetween('date', [$now->toDateString(), $maxDate->toDateString()])
                ->whereIn('status', ['pending', 'confirmed', 'completed'])
                ->get()
                ->groupBy(fn($app) => $app->date . '_' . $app->start_time);

            // 3. Traer las indisponibilidades (vacaciones, congresos) del médico
            $unavailabilities = DB::table('unavailabilities')
                ->where('doctor_id', $doctor->id)
                ->where(fn($q) => $q->whereNull('address_id')->orWhere('address_id', $targetAddressId))
                ->where('start_date', '<=', $maxDate->toDateString())
                ->where('end_date', '>=', $now->toDateString())
                ->get();

            // Escaneamos el horizonte dinámico permitido por el plan/configuración del médico
            for ($dayOffset = 0; $dayOffset <= $maxAdvanceDays; $dayOffset++) {
                $evalDate = Carbon::now()->addDays($dayOffset);
                $evalDateString = $evalDate->toDateString();
                
                // 🛡️ REGLA DE PAÍS AUTOMÁTICA NATIVA
                if (array_key_exists($evalDateString, $colombianHolidays)) {
                    continue; // Pasa al siguiente día (queda gris en Flatpickr)
                }

                // 🔒 OPTIMIZACIÓN QUIRÚRGICA: Removimos la doble asignación redundante de variables
                $evalDayOfWeek = $evalDate->dayOfWeekIso; 

                // Si el médico no atiende este día de la semana, pasamos al siguiente día
                if (!isset($baseSchedules[$evalDayOfWeek])) {
                    continue;
                }

                // Verificar si el día completo está bloqueado por indisponibilidad
                $isDateUnavailable = $unavailabilities->contains(function ($unavail) use ($evalDateString) {
                    return $evalDateString >= $unavail->start_date && 
                           $evalDateString <= $unavail->end_date && 
                           is_null($unavail->start_time);
                });

                if ($isDateUnavailable) {
                    continue;
                }

                $hasAtLeastOneFreeSlot = false;

                // Evaluamos cada franja horaria de este día específico
                foreach ($baseSchedules[$evalDayOfWeek] as $sched) {
                    $slotDateTime = Carbon::parse($evalDateString . ' ' . $sched->start_time);

                    // Regla de Inmediatez: Filtrar horas pasadas si es hoy
                    if ($dayOffset === 0 && $sched->start_time < $now->toTimeString()) {
                        continue;
                    }

                    // Regla de Negocio: Validar horas mínimas de anticipación (min_notice_hours)
                    if ($slotDateTime->diffInHours($now, false) * -1 < $minNoticeHours) {
                        continue;
                    }

                    // Verificar si la franja específica está bloqueada por una indisponibilidad parcial (por horas)
                    $isSlotInUnavailability = $unavailabilities->contains(function ($unavail) use ($evalDateString, $sched) {
                        if ($evalDateString >= $unavail->start_date && $evalDateString <= $unavail->end_date && !is_null($unavail->start_time)) {
                            return $sched->start_time >= $unavail->start_time && $sched->start_time < $unavail->end_time;
                        }
                        return false;
                    });

                    if ($isSlotInUnavailability) {
                        continue;
                    }

                    // Verificar en la matriz cargada si la cita ya está ocupada
                    $isBooked = isset($bookedAppointments[$evalDateString . '_' . $sched->start_time]);

                    if (!$isBooked) {
                        $hasAtLeastOneFreeSlot = true;
                        // Si es el primer slot libre que encontramos en todo el bucle, lo fijamos como el próximo turno disponible
                        if (empty($enabledDates)) {
                            $nextAvailableDate = $evalDateString;
                        }
                        break; // Si ya encontramos un espacio libre, este día es válido (se pintará de verde)
                    }
                }

                // Si el día tiene al menos un slot disponible, se añade a la lista blanca para Flatpickr
                if ($hasAtLeastOneFreeSlot) {
                    $enabledDates[] = $evalDateString;
                }
            }
        }

        // Resolver pre-selección de sede física o virtual para inyectar en Alpine.js
        if ($addressId) {
            $preSelectedAddress = $doctor->addresses->where('id', $addressId)->first();
        }
        
        return view('public.public-profile', [
            'partner'            => $doctor,
            'profileType'        => 'doctor',
            'currentSpecialty'   => $currentSpecialty,
            'seoTitle'           => "Dr(a). " . ucfirst($doctor->user->name) . ' | Agendamiento en Línea',
            'seoDescription'     => $doctor->bio ?? 'Especialista profesional calificado, agenda tu cita médica en línea.',
            'metaRobots'         => 'index, follow',
            'preSelectedAddress' => $preSelectedAddress,
            'fromClinicId'       => $fromClinicId,
            'nextAvailableDate'  => $nextAvailableDate, // Mantiene compatibilidad total
            'enabledDates'       => $enabledDates       // 🧬 Inyección clave para pintar el calendario real
        ]);
    }

    /**
     * Muestra la pantalla del perfil unificado (Clínica o Doctor de opendoctor.online).
     * URL: /medical-partner/{slug}
     */
    public function show(Request $request, $slug)
    {
        $addressId = $request->input('address_id');
        
        // 🔒 BLINDAJE DE TIEMPO REAL: Forzamos el huso horario local de Colombia para erradicar desfases en el calendario
        $tz = 'America/Bogota';
        $now = Carbon::now($tz);
        $todayColombia = $now->toDateString(); // Variable raíz para minDate seguro en Flatpickr
        $currentTime = $now->toTimeString();

        // 🔒 CAPTURA MAESTRA DE LA ESPECIALIDAD REQUERIDA (Soporta Slug e ID numérico)
        $targetSpecialtySlug = $request->input('specialty');
        
        $currentSpecialty = Specialty::where('status', true)
            ->where(function($query) use ($targetSpecialtySlug) {
                $query->where('slug', $targetSpecialtySlug)->orWhere('id', $targetSpecialtySlug);
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

            // 🚀 OPTIMIZACIÓN DE MEMORIA: Pluck directo desde la colección cargada en memoria, Cero SQL extra
            $doctorIds = $clinic->doctors->pluck('id')->toArray();
            $unifiedSlots = [];
            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                $evalDate = $now->copy()->addDays($dayOffset);
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
            
            // FÓRMULA DE SÍNTESIS DEPURADA: Prepara las tarjetas moleculares para la clínica
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
                
                $assignedAddressId = !empty($activeAddressIds) ? $activeAddressIds[0] : $address->id;
                $targetAddressModel = $clinicAddresses->firstWhere('id', $assignedAddressId) ?? $address;

                $results[] = [
                    'id'                 => $doc->id,
                    'slug'               => $doc->slug ?? $doc->user->slug,
                    'type'               => 'doctor',
                    'title'              => ($doc->gender === 'female' ? 'Dra. ' : 'Dr. ') . ucfirst($doc->user->name),
                    'subtitle'           => "Sede " . $targetAddressModel->name . " • " . $targetAddressModel->address,
                    'rating'             => $doc->rating ?? 5,
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

        $fromClinicId = $request->input('from_clinic') ?? $request->input('clinic_id');
        $preSelectedAddress = null;

        // Carga base unificada de datos del médico y sus especialidades ordenadas
        $doctor->load([
            'user',
            'specialties' => function ($query) use ($targetSpecialtySlug) {
                if ($targetSpecialtySlug && isset($targetSpecialtySlug)) {
                    $query->orderByRaw('CASE WHEN specialties.id = ? THEN 0 ELSE 1 END', [$targetSpecialtySlug])
                          ->orderBy('name', 'asc');
                } else {
                    $query->orderBy('name', 'asc');
                }
            }
        ]);

        if (!$currentSpecialty) {
            $currentSpecialty = $doctor->specialties->first() ?? (object) [
                'id' => 1, 'name' => 'Consulta General', 'slug' => 'general'
            ];
        }

        // =========================================================================
        // 🏢 CASO 1: EL MÉDICO ESTÁ OPERANDO COMO STAFF DE UNA CLÍNICA (?from_clinic=1)
        // =========================================================================
        if ($fromClinicId) {
            $parentClinic = Clinic::where('id', $fromClinicId)->where('validation_status', 'approved')->first();

            if (!$parentClinic) {
                abort(404, 'La institución médica vinculada no se encuentra activa.');
            }

            // Traer únicamente las sedes oficiales de la clínica
            $clinicAddresses = Address::where('status', true)->where('clinic_id', $fromClinicId)->get();

            // Detección híbrida: inyectamos servicios institucionales a cada sede
            foreach ($clinicAddresses as $currentAddr) {
                if ($currentAddr->type === 'virtual') {
                    $rawServices = DB::table('address_service')
                        ->join('services', 'address_service.service_id', '=', 'services.id')
                        ->where('address_service.address_id', $currentAddr->id)
                        ->where('services.active', true)
                        ->select('services.id', 'services.name', 'address_service.price', 'address_service.duration')
                        ->distinct()->get();
                } else {
                    $rawServices = DB::table('address_service')
                        ->join('services', 'address_service.service_id', '=', 'services.id')
                        ->join('service_specialty', 'services.id', '=', 'service_specialty.service_id')
                        ->join('doctor_specialty', 'service_specialty.specialty_id', '=', 'doctor_specialty.specialty_id')
                        ->where('address_service.address_id', $currentAddr->id)
                        ->where('services.active', true)
                        ->where('doctor_specialty.doctor_id', $doctor->id)
                        ->select('services.id', 'services.name', 'address_service.price', 'address_service.duration')
                        ->distinct()->get();
                }

                $formattedServices = $rawServices->map(function($srv) {
                    $srv->pivot = (object)['price' => $srv->price, 'duration' => $srv->duration];
                    return $srv;
                });

                $currentAddr->setRelation('services', $formattedServices);
            }

            $doctor->setRelation('addresses', $clinicAddresses);
            session(['current_clinic_user_id' => $parentClinic->user_id]);
            session(['current_doctor_id' => $doctor->user_id]);
        }
        // =========================================================================
        // 🩺 CASO 2: EL MÉDICO OPERA EN SU CONSULTORIO PARTICULAR AUTÓNOMO
        // =========================================================================
        else {
            $doctor->load(['addresses' => function ($query) use ($doctor) {
                $query->where('status', true)
                    ->whereNull('clinic_id')
                    ->with(['city', 'services' => function($query) use ($doctor) {
                        $query->where('services.active', true)
                            ->withPivot('price', 'duration')
                            ->whereIn('services.id', function($subQuery) use ($doctor) {
                                $subQuery->select('service_specialty.service_id')
                                    ->from('service_specialty')->where('service_specialty.user_id', $doctor->user_id);
                            });
                    }]);
            }]);

            session(['current_doctor_id' => $doctor->user_id]);
            session()->forget('current_clinic_user_id');
        }
        // =========================================================================
        // 🔒 ALGORITMO HÍBRIDO AVANZADO: PREPARACIÓN DEL HORIZONTE PREDICTIVO
        // =========================================================================
        $nextAvailableDate = $todayColombia; // Forzado de tiempo seguro local
        $targetAddressId = null;

        if ($addressId) {
            $targetAddressId = (int)$addressId;
        } elseif ($doctor->addresses->isNotEmpty()) {
            $targetAddressId = (int)$doctor->addresses->first()->id;
        }

        $enabledDates = []; 
        $enabledDays = [];

        if ($targetAddressId) {
            $settings = DB::table('doctor_settings')->where('doctor_id', $doctor->id)->first();
            $minNoticeHours = $settings->min_notice_hours ?? 2;
            $maxAdvanceDays = $settings->max_advance_days ?? 30;

            $maxDate = $now->copy()->addDays($maxAdvanceDays);
            $colombianHolidays = ColombiaHolidayService::getHolidays($now->year);

            // 1. Traer todos los horarios configurados por el médico en esta sede
            $baseSchedules = DB::table('schedules')
                ->where('address_id', $targetAddressId)
                ->where('doctor_id', $doctor->id)
                ->get()
                ->groupBy('day');

            // 🚀 Extracción indexada de días teóricos operativos (0=Dom, 1=Lun ... 6=Sab)
            $enabledDays = $baseSchedules->keys()->map(function($day) {
                return $day === 7 ? 0 : $day;
            })->unique()->values()->toArray();

            // 2. Traer todas las citas activas/reservadas en este rango para evitar N+1
            $bookedAppointments = Appointment::where('address_id', $targetAddressId)
                ->where('doctor_id', $doctor->id)
                ->whereBetween('date', [$now->toDateString(), $maxDate->toDateString()])
                ->whereIn('status', ['pending', 'confirmed', 'completed'])
                ->get()
                ->groupBy(fn($app) => $app->date . '_' . $app->start_time);

            // 3. Traer las indisponibilidades (vacaciones, congresos) del médico
            $unavailabilities = DB::table('unavailabilities')
                ->where('doctor_id', $doctor->id)
                ->where(fn($q) => $q->whereNull('address_id')->orWhere('address_id', $targetAddressId))
                ->where('start_date', '<=', $maxDate->toDateString())
                ->where('end_date', '>=', $now->toDateString())
                ->get();
            // 4. ESCANEO DINÁMICO DEL HORIZONTE DE DISPONIBILIDAD
            for ($dayOffset = 0; $dayOffset <= $maxAdvanceDays; $dayOffset++) {
                $evalDate = $now->copy()->addDays($dayOffset);
                $evalDateString = $evalDate->toDateString();
                
                if (array_key_exists($evalDateString, $colombianHolidays)) {
                    continue; 
                }

                $evalDayOfWeek = $evalDate->dayOfWeekIso; 

                if (!isset($baseSchedules[$evalDayOfWeek])) {
                    continue;
                }

                $isDateUnavailable = $unavailabilities->contains(function ($unavail) use ($evalDateString) {
                    return $evalDateString >= $unavail->start_date && 
                           $evalDateString <= $unavail->end_date && 
                           is_null($unavail->start_time);
                });

                if ($isDateUnavailable) {
                    continue;
                }

                $hasAtLeastOneFreeSlot = false;

                foreach ($baseSchedules[$evalDayOfWeek] as $sched) {
                    $startTimeStr = $sched->start_time instanceof Carbon ? $sched->start_time->format('H:i:s') : Carbon::parse($sched->start_time)->format('H:i:s');
                    $slotDateTime = Carbon::parse($evalDateString . ' ' . $startTimeStr, 'America/Bogota');

                    // 🔒 Filtro matemático estricto de inmediatez y aviso mínimo en Colombia
                    if ($slotDateTime->lessThan($now->copy()->addHours((int)$minNoticeHours))) {
                        continue;
                    }

                    $isSlotInUnavailability = $unavailabilities->contains(function ($unavail) use ($evalDateString, $startTimeStr) {
                        if ($evalDateString >= $unavail->start_date && $evalDateString <= $unavail->end_date && !is_null($unavail->start_time)) {
                            $uStart = $unavail->start_time instanceof Carbon ? $unavail->start_time->format('H:i:s') : Carbon::parse($unavail->start_time)->format('H:i:s');
                            $uEnd = $unavail->end_time instanceof Carbon ? $unavail->end_time->format('H:i:s') : Carbon::parse($unavail->end_time)->format('H:i:s');
                            return $startTimeStr >= $uStart && $startTimeStr < $uEnd;
                        }
                        return false;
                    });

                    if ($isSlotInUnavailability) {
                        continue;
                    }

                    $isBooked = isset($bookedAppointments[$evalDateString . '_' . $startTimeStr]);

                    if (!$isBooked) {
                        $hasAtLeastOneFreeSlot = true;
                        if (empty($enabledDates)) {
                            $nextAvailableDate = $evalDateString;
                        }
                        break; 
                    }
                }

                if ($hasAtLeastOneFreeSlot) {
                    $enabledDates[] = $evalDateString;
                }
            }
        }

        if ($addressId) {
            $preSelectedAddress = $doctor->addresses->where('id', $addressId)->first();
        }
        
        // 🔒 DESPACHO INTEGRAL CON RETORNO SEGURO A PRODUCCIÓN
        return view('public.public-profile', [
            'partner'            => $doctor,
            'profileType'        => 'doctor',
            'currentSpecialty'   => $currentSpecialty,
            'seoTitle'           => "Dr(a). " . ucfirst($doctor->user->name) . ' | Agendamiento en Línea',
            'seoDescription'     => $doctor->bio ?? 'Especialista profesional calificado, agenda tu cita médica en línea.',
            'metaRobots'         => 'index, follow',
            'preSelectedAddress' => $preSelectedAddress,
            'fromClinicId'       => $fromClinicId,
            'nextAvailableDate'  => $nextAvailableDate,
            'enabledDates'       => $enabledDates,
            'enabledDays'        => $enabledDays ?? [],  // Entrega los índices numéricos para los fondos grises clickeables
            'todayColombia'      => $todayColombia       // Fuerza el minDate estricto contra el ayer real en el frontend
        ]);
    }
}
