<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\Address;
use App\Models\Specialty;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\ColombiaHolidayService;

class PublicProfileController extends Controller
{              
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
                    $endTimeStr   = $sched->end_time instanceof Carbon   ? $sched->end_time->format('H:i:s')   : Carbon::parse($sched->end_time)->format('H:i:s');

                    $earliestBookable = $now->copy()->addHours((int)$minNoticeHours);
                    $slotEnd = Carbon::parse($evalDateString . ' ' . $endTimeStr, 'America/Bogota');

                    // 🔒 Solo descartamos el rango si ya terminó completamente antes del mínimo de anticipación
                    if ($slotEnd->lessThanOrEqualTo($earliestBookable)) {
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

        $langNames = ['es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués', 'fr' => 'Francés', 'de' => 'Alemán'];
        $rawLang = $doctor->languages;
        $decodedLang = is_array($rawLang) ? $rawLang : (json_decode($rawLang, true) ?? []);

        $langFlags = [
            'co' => 'co',
            'es' => 'es',
            'en' => 'us',
            'pt' => 'br',
            'fr' => 'fr',
            'de' => 'de',
            'it' => 'it',
            'zh' => 'cn',
            'ar' => 'sa',
        ];
        
        $languages = array_map(fn($code) => [
            'code' => $code,
            'name' => $langNames[$code] ?? strtoupper($code),
            'flag' => $langFlags[$code] ?? 'un', // 'un' = bandera ONU como fallback
        ], $decodedLang);
                        
        // 🔒 DESPACHO INTEGRAL CON RETORNO SEGURO A PRODUCCIÓN
        return view('public.public-profile', [
            'partner'            => $doctor,
            'profileType'        => 'doctor',
            'currentSpecialty'   => $currentSpecialty,
            'languages'          => $languages,
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
