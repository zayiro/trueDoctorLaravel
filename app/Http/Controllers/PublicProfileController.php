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

class PublicProfileController extends Controller
{
    /**
     * Muestra el perfil público unificado de un Doctor o una Clínica basada en su Slug.
     */
    public function show($slug)
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
     * Devuelve la disponibilidad de FullCalendar según la infraestructura del Doctor.
     */
    public function getAvailability(Doctor $doctor, Request $request)
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
    public function preview(Request $request)
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
    public function success(Appointment $appointment)
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
