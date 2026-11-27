<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\Certificates\CertificateException;
use App\Services\Certificates\CertificateIssuer;
use App\Services\Certificates\CertificateNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * El doctor dueño de la cita emite la incapacidad médica de ESA cita.
 * Las reglas de fondo viven en CertificateIssuer + IncapacityRules. Descargar, anular y reenviar usan las rutas de certificados.
 * No toca Zoom, la historia clínica ni pagos.
 */
class PartnerIncapacityController extends Controller
{
    /** POST .../clinical-record/incapacities */
    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = Auth::user();
        $doctor = $user?->doctor;

        if (!$user || $user->role !== 'doctor' || !$doctor instanceof Doctor || (int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(403, 'Solo el especialista médico asignado a esta cita puede emitir incapacidades de ella.');
        }

        $data = $request->validate([
            'origin'                  => ['required', 'string', 'max:10'],
            'start_date'              => ['required', 'date_format:Y-m-d'],
            'days'                    => ['required', 'integer', 'min:1', 'max:999'],
            'is_extension'            => ['nullable', 'boolean'],
            'previous_certificate_id' => ['nullable', 'integer'],
            'retroactive_reason'      => ['nullable', 'string', 'max:500'],
            'cie10_code'              => ['required', 'string', 'max:255'],
            'declaration'             => ['accepted'],
        ], [
            'origin.required'       => 'Elige el origen de la incapacidad.',
            'start_date.required'   => 'Escribe la fecha de inicio de la incapacidad.',
            'start_date.date_format' => 'La fecha de inicio no es válida.',
            'days.required'         => 'Escribe cuántos días de incapacidad.',
            'days.integer'          => 'Los días de incapacidad deben ser un número entero.',
            'days.min'              => 'La incapacidad debe tener al menos 1 día.',
            'cie10_code.required'   => 'Escribe o elige el diagnóstico con su código CIE-10.',
            'declaration.accepted'  => 'Para emitir la incapacidad debes aceptar la declaración del profesional.',
        ]);

        try {
            $certificate = app(CertificateIssuer::class)->issue($appointment, $doctor, 'incapacity', [
                'declaration' => true,
                'incapacity'  => [
                    'origin'                  => (string) $data['origin'],
                    'start_date'              => (string) $data['start_date'],
                    'days'                    => (int) $data['days'],
                    'is_extension'            => $request->boolean('is_extension'),
                    'previous_certificate_id' => $data['previous_certificate_id'] ?? null,
                    'retroactive_reason'      => $data['retroactive_reason'] ?? null,
                    'cie10_code'              => (string) $data['cie10_code'],
                ],
            ], $request->ip());
        } catch (CertificateException $e) {
            return back()->withInput()->with('error', $e->getMessage())->with('cr_notice', ['type' => 'error', 'text' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('incapacity.issue.failed', ['appointment_id' => $appointment->id, 'error' => substr($e->getMessage(), 0, 300)]);

            $text = 'No pudimos emitir la incapacidad en este momento. Intenta de nuevo en unos minutos; si el problema continúa, contacta a soporte.';

            return back()->withInput()->with('error', $text)->with('cr_notice', ['type' => 'error', 'text' => $text]);
        }

        // El correo al paciente nunca debe impedir ni deshacer la emisión.
        $mail = app(CertificateNotifier::class)->notify($certificate);
        $text = 'Incapacidad emitida con el código ' . $certificate->code . '. ' . $mail['message']
            . ($mail['ok'] ? '' : ' Puedes reenviarla desde el panel de certificados.');

        return back()->with('success', $text)->with('cr_notice', ['type' => $mail['ok'] ? 'success' : 'error', 'text' => $text]);
    }
}
