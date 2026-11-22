<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorSettingController extends Controller
{
    /**
     * Mostrar el formulario de configuración
     */
    public function edit()
    {
        // Se asume que el usuario autenticado tiene un perfil de doctor asociado
        $doctor = Auth::user()->doctor;
        
        // Cargar las configuraciones o crearlas con valores por defecto si no existen
        $settings = $doctor->settings()->firstOrCreate(['doctor_id' => $doctor->id]);
        
        return view('partner.settings.edit', ['settings' => $settings, 'owner' => $doctor]);
    }

    /**
     * Procesar y guardar los cambios de la configuración.
     * Blindado contra inyección maliciosa de características deshabilitadas.
     */
    public function update(Request $request)
    {
        $request->validate([
            'accepts_online_payments'    => 'boolean',
            'currency'                   => 'required|string|size:3',
            'min_notice_hours'           => 'required|integer|min:0|max:168', 
            'max_advance_days'           => 'required|integer|min:1|max:365',  
            'buffer_time_minutes'        => 'required|integer|min:0|max:60',
            'max_appointments_per_day'   => 'nullable|integer|min:1|max:100',
            'allow_patient_cancellation' => 'boolean',
            'cancellation_notice_hours'  => 'required|integer|min:0|max:168',
            'allow_patient_rescheduling' => 'boolean',
            'virtual_meeting_platform'   => 'required|string|in:internal,zoom,meet,teams',
            'google_calendar_sync'       => 'boolean',
            'email_notifications'        => 'boolean',
            'whatsapp_notifications'     => 'boolean',
        ]);

        $doctor = Auth::user()->doctor;

        // Lista cerrada: solo los campos de este formulario. Nada más del request llega a la base (ej. plan_id).
        $data = $request->only([
            'accepts_online_payments', 'currency', 'min_notice_hours', 'max_advance_days',
            'buffer_time_minutes', 'max_appointments_per_day', 'allow_patient_cancellation', 'cancellation_notice_hours', 'allow_patient_rescheduling',
            'virtual_meeting_platform', 'google_calendar_sync', 'email_notifications', 'whatsapp_notifications',
        ]);
        
        $booleans = [
            'accepts_online_payments', 'allow_patient_cancellation',
            'allow_patient_rescheduling', 'google_calendar_sync', 'email_notifications',
            'whatsapp_notifications'
        ];

        // 1. Mapeo inicial de los toggles booleanos según su presencia en el Request
        foreach ($booleans as $field) {
            $data[$field] = $request->has($field);
        }

        // Política de pago de citas presenciales (exige aceptar la comisión de servicio al activar el pago en línea).
        $data = array_merge($data, \App\Services\Payments\PresentialPolicy::prepare($doctor, $request));

        // 3. Persistencia segura en la base de datos
        $doctor->settings()->update($data);

        \App\Services\Payments\PresentialPolicy::recordAcceptance($doctor, $data, $request);

        // 4. Redirección limpia al alias corporativo de tu sistema
        return redirect()->route('partner.settings.edit')
            ->with('success', 'Configuraciones de la consulta actualizadas correctamente.');
    }

}
