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
        
        return view('partner.settings.edit', compact('settings'));
    }

    /**
     * Procesar y guardar los cambios
     */
    public function update(Request $request)
    {
        $request->validate([
            'accepts_online_payments'    => 'boolean',
            'currency'                   => 'required|string|size:3',
            'min_notice_hours'           => 'required|integer|min:0|max:168', 
            'max_advance_days'           => 'required|integer|min:1|max:365',  
            'requires_approval'          => 'boolean',
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

        // Extraemos los datos del request exceptuando la metadata de Laravel
        $data = $request->except(['_token', '_method']);
        
        $booleans = [
            'accepts_online_payments', 'requires_approval', 'allow_patient_cancellation',
            'allow_patient_rescheduling', 'google_calendar_sync', 'email_notifications',
            'whatsapp_notifications'
        ];

        // 1. Mapeo inicial de los toggles booleanos según su presencia en el Request
        foreach ($booleans as $field) {
            $data[$field] = $request->has($field);
        }

        // 2. 👇 CANDADO DE SEGURIDAD: Regla de Exclusión Mutua para Pasarela de Pagos
        // Si el médico exige pago en línea inmediato, forzamos a que NO requiera aprobación manual.
        // Si el médico entra a su panel y marca el interruptor de "Activar Pagos Online" y también el de "Aprobación Manual", 
        // al presionar guardar, el backend priorizará el cobro inmediato. La base de datos guardará requires_approval = 0, 
        // lo que garantiza que las citas cobradas entren directamente confirmadas para el paciente.
        if ($data['accepts_online_payments'] === true && $data['requires_approval'] === true) {
            $data['requires_approval'] = false;
        }

        // 3. Persistencia segura en la base de datos
        $doctor->settings()->update($data);

        // 4. Redirección limpia al alias corporativo de tu sistema
        return redirect()->route('partner.settings.edit')
            ->with('success', 'Configuraciones de la consulta actualizadas correctamente.');
    }
}
