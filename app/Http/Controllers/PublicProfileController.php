<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\Service;
use App\Models\Address;
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
     * Muestra el perfil público unificado de un Doctor o una Clínica basada en su Slug.
     */
    public function show__($slug)
    {
        // 1. Buscamos primero en el catálogo maestro de Clínicas habilitadas
        $partner = Clinic::where('slug', $slug)
            ->where('validation_status', 'approved')
            ->where('active', true)
            ->first();

        $profileType = 'clinic';

        // 2. Si no es una clínica, buscamos en el catálogo de Especialistas Independientes
        if (!$partner) {
            $partner = Doctor::where('slug', $slug)
                ->where('validation_status', 'approved')
                ->where('active', true)
                ->first();

            $profileType = 'doctor';
        }

        // 3. Si no existe en ninguna de las dos entidades, arrojamos un 404 limpio
        if (!$partner) {
            abort(404, 'The requested medical profile does not exist or is under validation.');
        }

        // 4. Eager Loading (Carga previa adaptada a la Co-propiedad de opendoctor)
        if ($profileType === 'clinic') {
            $partner->load([
                'user',
                'specialties',
                'addresses' => function ($query) {
                    $query->where('status', true)
                          ->with(['city', 'services' => function ($q) {
                              $q->where('active', true);
                          }]);
                }
            ]);
            
            // Congelamos el ID de usuario de la clínica para el contexto transaccional
            session(['current_clinic_user_id' => $partner->user_id]);
            session()->forget('current_doctor_id');

            $seoName = $partner->name;
            $seoDescription = $partner->bio ?? 'Centro médico e institucional verificado por OpenDoctor.';
        } else {
            $partner->load([
                'user',
                'specialties',
                'addresses' => function ($query) {
                    $query->where('status', true)
                          ->with(['city', 'services' => function ($q) {
                              $q->where('active', true);
                          }]);
                }
            ]);

            // Congelamos el ID de usuario del médico independiente
            session(['current_doctor_id' => $partner->user_id]);
            session()->forget('current_clinic_user_id');

            $seoName = "Dr(a). " . ucfirst($partner->user->name);
            $seoDescription = $partner->bio ?? 'Especialista profesional calificado, agenda tu cita médica en línea.';
        }

        // 5. Configuración SEO Dinámica
        $seoTitle = $seoName . ' | Agendamiento en Línea';
        $metaRobots = "index, follow";
        
        return view('public.public-profile', [
            'partner'        => $partner, 
            'profileType'    => $profileType, 
            'seoTitle'       => $seoTitle, 
            'seoDescription' => $seoDescription, 
            'metaRobots'     => $metaRobots
        ]);
    }

    /**
     * Muestra la pantalla intermedia de decisión para clínicas o el perfil directo del doctor.
     */
    public function show(Request $request, $slug)
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

            return view('public.profiles.clinic_decision', [
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
