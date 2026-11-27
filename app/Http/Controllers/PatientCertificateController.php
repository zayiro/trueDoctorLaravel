<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * El paciente descarga SUS certificados (solo el dueño; nunca los de otro paciente).
 */
class PatientCertificateController extends Controller
{
    /** GET /patient/certificates/{certificate}/download */
    public function download(Certificate $certificate)
    {
        $patient = Auth::user()?->patient;

        // No revelamos si el código existe para otro paciente: mismo mensaje.
        if (!$patient || (int) $certificate->patient_id !== (int) $patient->id) {
            return $this->back('No encontramos ese certificado en tu cuenta.');
        }

        if ($certificate->isRevoked()) {
            return $this->back('Este certificado fue anulado por tu médico y ya no se puede descargar. Si lo necesitas, pídele que emita uno nuevo.');
        }

        try {
            $path = $certificate->signedDocument?->storage_path;
            $disk = Storage::disk((string) config('certificates.disk', 'private'));

            if ($path && $disk->exists($path)) {
                return $disk->download($path, 'certificado-' . $certificate->code . '.pdf', [
                    'Content-Type'  => 'application/pdf',
                    'Cache-Control' => 'private, no-store',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('certificate.patient_download.failed', ['certificate_id' => $certificate->id, 'error' => substr($e->getMessage(), 0, 300)]);
        }

        return $this->back('No pudimos abrir el PDF en este momento. Intenta de nuevo en unos minutos.');
    }

    private function back(string $message): RedirectResponse
    {
        return redirect()->route('patient.appointments.index')->with('error', $message);
    }
}
