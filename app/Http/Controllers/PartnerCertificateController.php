<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Certificate;
use App\Models\Doctor;
use App\Services\Certificates\CertificateException;
use App\Services\Certificates\CertificateIssuer;
use App\Services\Certificates\CertificateNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * El doctor dueño de la cita emite, descarga y anula los certificados de ESA cita.
 * Las reglas de fondo (cita completada, historia firmada, tope por cita) viven en CertificateIssuer.
 * No toca Zoom, la historia clínica ni pagos.
 */
class PartnerCertificateController extends Controller
{
    /** Solo el médico asignado a la cita. */
    private function doctorFor(Appointment $appointment): Doctor
    {
        $user = Auth::user();
        $doctor = $user?->doctor;

        if (!$user || $user->role !== 'doctor' || !$doctor || (int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(403, 'Solo el especialista médico asignado a esta cita puede gestionar sus certificados.');
        }

        return $doctor;
    }

    /** POST .../clinical-record/certificates */
    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        $doctor = $this->doctorFor($appointment);

        $data = $request->validate([
            'type'                         => ['required', Rule::in(array_keys((array) config('certificates.types', [])))],
            'declaration'                  => ['accepted'],
            'body'                         => ['nullable', 'string', 'max:2000'],
            'include_diagnosis'            => ['nullable', 'boolean'],
            'patient_authorized_diagnosis' => ['nullable', 'boolean'],
            'diagnosis_text'               => ['nullable', 'string', 'max:500'],
            'cie10_code'                   => ['nullable', 'string', 'max:20'],
        ], [
            'type.required'        => 'Elige qué certificado quieres emitir.',
            'type.in'              => 'Ese tipo de certificado no existe.',
            'declaration.accepted' => 'Para emitir el certificado debes aceptar la declaración del profesional.',
            'body.max'             => 'El contenido del certificado es muy largo (máximo 2000 caracteres).',
            'diagnosis_text.max'   => 'El diagnóstico es muy largo (máximo 500 caracteres).',
            'cie10_code.max'       => 'El código CIE-10 es muy largo.',
        ]);

        try {
            $certificate = app(CertificateIssuer::class)->issue($appointment, $doctor, (string) $data['type'], [
                'declaration'                  => true,
                'body'                         => $data['body'] ?? null,
                'include_diagnosis'            => $request->boolean('include_diagnosis'),
                'patient_authorized_diagnosis' => $request->boolean('patient_authorized_diagnosis'),
                'diagnosis_text'               => $data['diagnosis_text'] ?? null,
                'cie10_code'                   => $data['cie10_code'] ?? null,
            ], $request->ip());
        } catch (CertificateException $e) {
            return back()->withInput()->with('error', $e->getMessage())->with('cr_notice', ['type' => 'error', 'text' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('certificate.issue.failed', ['appointment_id' => $appointment->id, 'error' => substr($e->getMessage(), 0, 300)]);

            return back()->withInput()->with('error', 'No pudimos emitir el certificado en este momento. Intenta de nuevo en unos minutos; si el problema continúa, contacta a soporte.')->with('cr_notice', ['type' => 'error', 'text' => 'No pudimos emitir el certificado en este momento. Intenta de nuevo en unos minutos; si el problema continúa, contacta a soporte.']);
        }

        // El correo al paciente nunca debe impedir ni deshacer la emisión.
        $mail = app(CertificateNotifier::class)->notify($certificate);
        $text = 'Certificado emitido con el código ' . $certificate->code . '. ' . $mail['message']
            . ($mail['ok'] ? '' : ' Puedes reenviarlo desde el panel de certificados.');

        return back()->with('success', $text)->with('cr_notice', ['type' => $mail['ok'] ? 'success' : 'error', 'text' => $text]);
    }

    /** GET .../clinical-record/certificates/{certificate}/download */
    public function download(Appointment $appointment, Certificate $certificate)
    {
        $this->doctorFor($appointment);
        abort_unless((int) $certificate->appointment_id === (int) $appointment->id, 404);

        try {
            $path = $certificate->signedDocument?->storage_path;
            $disk = Storage::disk((string) config('certificates.disk', 'private'));

            if ($path && $disk->exists($path)) {
                return $disk->response($path, 'certificado-' . $certificate->code . '.pdf', [
                    'Content-Type'  => 'application/pdf',
                    'Cache-Control' => 'private, no-store',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('certificate.download.failed', ['certificate_id' => $certificate->id, 'error' => substr($e->getMessage(), 0, 300)]);
        }

        return back()->with('error', 'No pudimos abrir el PDF de este certificado en este momento. Intenta de nuevo en unos minutos.')->with('cr_notice', ['type' => 'error', 'text' => 'No pudimos abrir el PDF de este certificado en este momento. Intenta de nuevo en unos minutos.']);
    }

    /** POST .../clinical-record/certificates/{certificate}/revoke */
    public function revoke(Request $request, Appointment $appointment, Certificate $certificate): RedirectResponse
    {
        $doctor = $this->doctorFor($appointment);
        abort_unless((int) $certificate->appointment_id === (int) $appointment->id, 404);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'reason.required' => 'Escribe el motivo de la anulación.',
            'reason.min'      => 'El motivo de la anulación es muy corto (mínimo 5 caracteres).',
            'reason.max'      => 'El motivo de la anulación es muy largo (máximo 255 caracteres).',
        ]);

        try {
            app(CertificateIssuer::class)->revoke($certificate, $doctor, (string) $data['reason'], Auth::id());
        } catch (CertificateException $e) {
            return back()->with('error', $e->getMessage())->with('cr_notice', ['type' => 'error', 'text' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('certificate.revoke.failed', ['certificate_id' => $certificate->id, 'error' => substr($e->getMessage(), 0, 300)]);

            return back()->with('error', 'No pudimos anular el certificado en este momento. Intenta de nuevo en unos minutos.')->with('cr_notice', ['type' => 'error', 'text' => 'No pudimos anular el certificado en este momento. Intenta de nuevo en unos minutos.']);
        }

        return back()->with('success', 'El certificado ' . $certificate->code . ' fue anulado. La verificación pública ya lo muestra como anulado.')->with('cr_notice', ['type' => 'success', 'text' => 'El certificado ' . $certificate->code . ' fue anulado. La verificación pública ya lo muestra como anulado.']);
    }

    /** POST .../clinical-record/certificates/{certificate}/resend */
    public function resend(Appointment $appointment, Certificate $certificate): RedirectResponse
    {
        $this->doctorFor($appointment);
        abort_unless((int) $certificate->appointment_id === (int) $appointment->id, 404);

        if ($certificate->isRevoked()) {
            $text = 'El certificado está anulado: no se puede reenviar.';

            return back()->with('error', $text)->with('cr_notice', ['type' => 'error', 'text' => $text]);
        }

        $mail = app(CertificateNotifier::class)->notify($certificate);

        return back()->with($mail['ok'] ? 'success' : 'error', $mail['message'])->with('cr_notice', ['type' => $mail['ok'] ? 'success' : 'error', 'text' => $mail['message']]);
    }
}
