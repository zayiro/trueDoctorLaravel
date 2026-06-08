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
     * Muestra la pantalla del perfil directo del doctor.
     */
    public function show__(Request $request, $slug)
    {        
        $addressId = $request->input('address_id');
        $now = Carbon::now();

        // 1. Intentar buscar en el catálogo maestro de Clínicas aprobadas
        $clinic = Clinic::where('slug', $slug)
            ->where('validation_status', 'approved')
            ->where('active', true)
            ->first();

        if ($clinic) {
            $clinic->load([
                'user',
                'doctors' => function ($query) {
                    $query->where('active', true)
                          ->where('validation_status', 'approved')
                          ->with(['user', 'specialties']);
                },
                'addresses' => function ($query) use ($addressId) {
                    $query->where('status', true)->with(['city', 'services']);
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

            // Algoritmo para consolidar los siguientes 12 bloques disponibles (Inmediatez)
            $doctorIds = $clinic->doctors->pluck('id')->toArray();
            $unifiedSlots = [];
            $currentTime = $now->toTimeString();

            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                $evalDate = Carbon::now()->addDays($dayOffset);
                $evalDayOfWeek = $evalDate->dayOfWeekIso; // 1 = Lunes, 7 = Domingo

                $schedules = DB::table('schedules')
                    ->where('address_id', $address->id)
                    ->where('day', $evalDayOfWeek)
                    ->get();

                foreach ($schedules as $sched) {
                    if ($dayOffset === 0 && $sched->start_time < $currentTime) {
                        continue;
                    }

                    foreach ($doctorIds as $docId) {
                        $isBooked = Appointment::where('address_id', $address->id)
                            ->where('doctor_id', $docId)
                            ->where('date', $evalDate->toDateString())
                            ->where('start_time', $sched->start_time)
                            ->whereIn('status', ['pending', 'confirmed', 'completed'])
                            ->exists();

                        if (!$isBooked) {
                            $unifiedSlots[] = [
                                'date' => $evalDate->toDateString(),
                                'date_human' => $evalDate->translatedFormat('D d \d\e F'),
                                'start_time' => $sched->start_time,
                                'time_human' => Carbon::parse($sched->start_time)->format('g:i A'),
                                'doctor_id' => $docId
                            ];

                            if (count($unifiedSlots) >= 12) break 3; // Límite de 12 opciones rápidas
                        }
                    }
                }
            }

            session(['current_clinic_user_id' => $clinic->user_id]);
            session()->forget('current_doctor_id');

            return view('partner.clinic.public.decision', [
                'clinic'         => $clinic,
                'address'        => $address,
                'unifiedSlots'   => $unifiedSlots,
                'seoTitle'       => $clinic->name . ' | Selección de Turno',
                'seoDescription' => $clinic->bio ?? 'Elige entre atención inmediata o tu especialista preferido.'
            ]);
        }

        // 2. Si no es una clínica, buscamos en el catálogo de Especialistas Independientes
        $doctor = Doctor::where('slug', $slug)
            ->where('validation_status', 'approved')
            ->where('active', true)
            ->firstOrFail();

        $fromClinicId = $request->input('from_clinic');
        $preSelectedAddress = null;

        $doctor->load([
            'user',
            'specialties',
            'addresses' => function ($query) {
                $query->where('status', true)->with(['city', 'services']);
            }
        ]);

        if ($addressId && $fromClinicId) {
            $preSelectedAddress = $doctor->addresses->where('id', $addressId)->first();
        }

        session(['current_doctor_id' => $doctor->user_id]);
        session()->forget('current_clinic_user_id');

        return view('public.public-profile', [
            'partner'            => $doctor,
            'profileType'        => 'doctor',
            'seoTitle'           => "Dr(a). " . ucfirst($doctor->user->name) . ' | Agendamiento en Línea',
            'seoDescription'     => $doctor->bio ?? 'Especialista profesional calificado, agenda tu cita médica en línea.',
            'metaRobots'         => 'index, follow',
            'preSelectedAddress' => $preSelectedAddress,
            'fromClinicId'       => $fromClinicId
        ]);
    }

        /**
     * Muestra la pantalla del perfil unificado (Clínica o Doctor de opendoctor.online).
     * URL: /medical-partner/{slug}
     */
    public function show(Request $request, $slug)
    {        
        $addressId = $request->input('address_id');
        $now = Carbon::now();
        $currentTime = $now->toTimeString();

        // 🔒 CAPTURA MAESTRA DE LA ESPECIALIDAD REQUERIDA (Desde Query String del buscador o URLs del staff)
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
            $clinic->load([
                'user',
                'doctors' => function ($query) use ($currentSpecialty) {
                    $query->where('active', true)
                          ->where('validation_status', 'approved')
                          ->when($currentSpecialty, function($q) use ($currentSpecialty) {
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

            if (!$currentSpecialty && $clinic->doctors->isNotEmpty()) {
                $currentSpecialty = $clinic->doctors->first()->specialties->first();
            }

            // Algoritmo nativo de inmediatez del staff de la clínica
            $doctorIds = $clinic->doctors->pluck('id')->toArray();
            $unifiedSlots = [];

            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                $evalDate = Carbon::now()->addDays($dayOffset);
                $evalDayOfWeek = $evalDate->dayOfWeekIso;

                $schedules = DB::table('schedules')
                    ->where('address_id', $address->id)
                    ->where('day', $evalDayOfWeek)
                    ->get();

                foreach ($schedules as $sched) {
                    if ($dayOffset === 0 && $sched->start_time < $currentTime) {
                        continue;
                    }

                    foreach ($doctorIds as $docId) {
                        $isBooked = Appointment::where('address_id', $address->id)
                            ->where('doctor_id', $docId)
                            ->where('date', $evalDate->toDateString())
                            ->where('start_time', $sched->start_time)
                            ->whereIn('status', ['pending', 'confirmed', 'completed'])
                            ->exists();

                        if (!$isBooked) {
                            $targetDoctor = $clinic->doctors->firstWhere('id', $docId);

                            $unifiedSlots[] = [
                                'date'        => $evalDate->toDateString(),
                                'date_human'  => $evalDate->translatedFormat('D d \d\e F'),
                                'start_time'  => $sched->start_time,
                                'time_human'  => Carbon::parse($sched->start_time)->format('g:i A'),
                                'doctor_id'   => $docId,
                                'doctor_name' => $targetDoctor ? $targetDoctor->user->name : 'Especialista'
                            ];

                            if (count($unifiedSlots) >= 12) break 3;
                        }
                    }
                }
            }

                        // FÓRMULA DE SÍNTESIS DEPURADA: Prepara las tarjetas moleculares para la clínica
            $results = [];
            $clinicAddresses = $clinic->addresses;

            foreach ($clinic->doctors as $doc) {
                // 🚀 SOLUCIÓN MAESTRA: Buscamos en qué sedes de ESTA clínica tiene horarios reales configurados este médico
                $activeAddressIds = DB::table('schedules')
                    ->where('doctor_id', $doc->id)
                    ->whereIn('address_id', $clinicAddresses->pluck('id'))
                    ->pluck('address_id')
                    ->unique()
                    ->toArray();
                
                // Si el médico no tiene horarios en ninguna sede, usamos por defecto la sede evaluada en la URL
                $assignedAddressId = !empty($activeAddressIds) ? $activeAddressIds[0] : $address->id;

                // Buscamos el modelo físico de la sede asignada para construir el subtítulo exacto de la tarjeta
                $targetAddressModel = $clinicAddresses->firstWhere('id', $assignedAddressId) ?? $address;

                // Mapeamos el arreglo con los nombres exactos que espera tu index.blade.php molecular
                $results[] = [
                    'id'                 => $doc->id,
                    'slug'               => $doc->slug ?? $doc->user->slug,
                    'type'               => 'doctor', // Mantiene el render de tipo doctor en la terna
                    'title'              => ($doc->gender === 'female' ? 'Dra. ' : 'Dr. ') . ucfirst($doc->user->name),
                    
                    // 🏢 DINÁMICO: Muestra la sede física real de la clínica donde atiende el doctor
                    'subtitle'           => "Sede " . $targetAddressModel->name . " • " . $targetAddressModel->address,
                    'rating'             => $doc->rating ?? 5,
                    
                    // 🔒 CLAVE DEL ÉXITO: Este address_id alimenta el buscador de servicios en tu Blade
                    'address_id'         => $assignedAddressId, 
                    'active_address_ids' => !empty($activeAddressIds) ? $activeAddressIds : [$address->id],
                    'badge_text'         => $currentSpecialty ? $currentSpecialty->name : 'Especialista',
                    'next_turn'          => collect($unifiedSlots)->firstWhere('doctor_id', $doc->id)['time_human'] ?? null,
                    'user'               => $doc->user
                ];
            }

            // Inyección de Control de Sesión y Retorno de Vista Institucional Original
            session(['current_clinic_user_id' => $clinic->user_id]);
            session()->forget('current_doctor_id');

            return view('public.profiles.clinic_decision', [
                'clinic'          => $clinic,
                'specialty'       => $currentSpecialty,
                'clinicAddresses' => $clinicAddresses,
                'results'         => $results, // Transmite las tarjetas moleculares en parejas
                'address'         => $address,
                'unifiedSlots'    => $unifiedSlots,
                'seoTitle'        => $clinic->name . ' | Selección de Turno',
                'seoDescription'  => $clinic->bio ?? 'Elige entre atención inmediata o tu especialista preferido.'
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

        // 🚀 LÓGICA DE PRODUCCIÓN ORIGINAL RESTAURADA AL 100%
        $doctor->load([
            'user',
            'specialties' => function ($query) use ($currentSpecialty) {
                // 🔍 AQUÍ SE COLOCA LA LÍNEA:
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

        // Mapear la especialidad requerida para pintar de forma correcta en tus condicionales del Blade
        if (!$currentSpecialty) {
            $currentSpecialty = $doctor->specialties->first() ?? (object) [
                'id' => 1, 'name' => 'Consulta General', 'slug' => 'general'
            ];
        }

        // Tu validación condicional exacta original que protegía la visualización multi-sede
        if ($addressId && $fromClinicId) {
            $preSelectedAddress = $doctor->addresses->where('id', $addressId)->first();
        }

        session(['current_doctor_id' => $doctor->user_id]);
        session()->forget('current_clinic_user_id');

        return view('public.public-profile', [
            'partner'            => $doctor,
            'profileType'        => 'doctor',
            'currentSpecialty'   => $currentSpecialty, // Inyectado de forma segura
            'seoTitle'           => "Dr(a). " . ucfirst($doctor->user->name) . ' | Agendamiento en Línea',
            'seoDescription'     => $doctor->bio ?? 'Especialista profesional calificado, agenda tu cita médica en línea.',
            'metaRobots'         => 'index, follow',
            'preSelectedAddress' => $preSelectedAddress, // Se mantiene null si es independiente puro
            'fromClinicId'       => $fromClinicId
        ]);
    }

    /**
     * Devuelve la disponibilidad de FullCalendar según la infraestructura del Doctor.
     */
    public function getAvailability__(Doctor $doctor, Request $request)
    {
        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);
        
        $events = [];
        $schedules = $doctor->addresses()->with('schedules')->get()->pluck('schedules')->flatten();

        foreach ($schedules as $schedule) {
            // Generación interna de bloques de tiempo (FullCalendar)
        }

        return response()->json($events);
    }

    /**
     * Genera la previsualización de la cita antes de confirmar la transacción.
     */
    public function preview__(Request $request)
    {
        $service = Service::with('doctor.user')->findOrFail($request->service);
        $address = $request->address ? Address::with('city')->find($request->address) : null;
        $datetime = Carbon::parse($request->datetime);
        
        $notes = $request->notes;
        $phone = $request->phone;
        $identification = $request->identification;

        return view('public.appointments.preview', compact(
            'service', 'address', 'datetime', 'notes', 'phone', 'identification'
        ));
    }

    /**
     * Muestra la confirmación de éxito validando correctamente la tenencia del recurso.
     */
    public function success__(Appointment $appointment)
    {
        $patient = auth()->user()->patient;

        // 🔒 CONTROL DE ACCESO CORRECTO: Validamos contra el ID de la tabla patients, no el user_id directo
        if (!$patient || $appointment->patient_id !== $patient->id) { 
            abort(403, 'Unauthorized access to this medical receipt.'); 
        }

        $appointment->load(['doctor.user', 'service', 'address.city']);
        return view('public.appointments.success', compact('appointment'));
    }    
}
