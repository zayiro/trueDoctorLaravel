<?php

namespace App\Http\Controllers;

use App\Services\Payments\PresentialPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ajustes de la clínica: agenda, cancelaciones, notificaciones y pago de citas presenciales.
 * Misma pantalla que la del médico, pero sobre `clinic_settings`.
 * Lista cerrada de campos: nada más del request llega a la base (ej. plan_id, clinic_id, porcentajes especiales).
 */
class ClinicSettingController extends Controller
{
    private const FIELDS = [
        'currency', 'min_notice_hours', 'max_advance_days', 'buffer_time_minutes', 'max_appointments_per_day',
        'allow_patient_cancellation', 'cancellation_notice_hours', 'allow_patient_rescheduling',
        'virtual_meeting_platform', 'google_calendar_sync', 'email_notifications', 'whatsapp_notifications',
    ];

    private const BOOLEANS = [
        'allow_patient_cancellation', 'allow_patient_rescheduling',
        'google_calendar_sync', 'email_notifications', 'whatsapp_notifications',
    ];

    public function edit()
    {
        $clinic = Auth::user()->clinic;

        if (!$clinic) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'No encontramos el perfil de tu clínica. Si el problema continúa, escríbenos y lo resolvemos.');
        }

        $settings = $clinic->settings()->firstOrCreate(['clinic_id' => $clinic->id]);

        return view('partner.settings.edit', [
            'settings'   => $settings,
            'owner'      => $clinic,
            'formAction' => route('partner.clinic.settings.update'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
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

        $clinic = Auth::user()->clinic;

        if (!$clinic) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'No encontramos el perfil de tu clínica. Si el problema continúa, escríbenos y lo resolvemos.');
        }

        $data = $request->only(self::FIELDS);

        // Un toggle apagado no viaja en el formulario: su ausencia significa "false".
        foreach (self::BOOLEANS as $field) {
            $data[$field] = $request->has($field);
        }

        // Política de pago de citas presenciales (exige aceptar la comisión de servicio al activar el pago en línea).
        $data = array_merge($data, PresentialPolicy::prepare($clinic, $request));

        $clinic->settings()->firstOrCreate(['clinic_id' => $clinic->id])->update($data);

        PresentialPolicy::recordAcceptance($clinic, $data, $request);

        return redirect()->route('partner.clinic.settings.edit')
            ->with('success', 'Configuraciones de la clínica actualizadas correctamente.');
    }
}
