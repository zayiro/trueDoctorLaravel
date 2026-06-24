<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\DocumentSignatureService;

/*
Pasos concretos para arrancar:

Contacta a Certicámara (certicamara.com → Productos → Firma Digital) y solicita un certificado de Persona Jurídica para opendoctor.online — ellos te dan acceso sandbox primero
Instala barryvdh/laravel-dompdf y diseña la plantilla blade de la receta
Configura Laravel Queues con Redis o SQS para los jobs asíncronos
Implementa un endpoint de polling o websocket para notificarle al doctor cuando el PDF ya está firmado
*/
class PrescriptionController extends Controller
{
    public function sign(Request $request, Prescription $prescription): JsonResponse
    {
        $doctor = auth()->user()->doctor;

        abort_if($prescription->doctor_id !== $doctor->id, 403);
        abort_if($prescription->signedDocument()->exists(), 409, 'Esta receta ya fue firmada.');

        $signedAt = now();

        $signedDoc = app(DocumentSignatureService::class)->sign(
            view:      'documents.prescription',
            data:      [
                'prescription'  => $prescription->load('items'),
                'doctor'        => $doctor->load('specialties'),
                'patient'       => $prescription->patient,
                'issued_at'     => $signedAt->format('d/m/Y H:i:s'),
                'signed_at'     => $signedAt->format('d/m/Y H:i:s'),
                // Estos se renderizan en el PDF, el servicio los recalcula internamente
                'document_hash' => '(calculado)',
                'signature_hash'=> '(calculado)',
            ],
            doctor:    $doctor,
            patientId: $prescription->patient_id,
            type:      'prescription',
            signable:  $prescription,
            ip:        $request->ip()
        );

        // Segunda pasada: regenerar PDF con los hashes reales incrustados
        $signedDoc = $this->embedHashesInPdf($signedDoc, $prescription, $doctor, $signedAt);

        return response()->json([
            'message'        => 'Receta firmada correctamente.',
            'document_id'    => $signedDoc->id,
            'signature_hash' => $signedDoc->signature_hash,
            'signed_at'      => $signedDoc->signed_at->format('d/m/Y H:i:s'),
            'download_url'   => route('prescription.download', $signedDoc->id),
        ]);
    }

    public function verify(string $signatureHash): JsonResponse
    {
        $doc = SignedDocument::where('signature_hash', $signatureHash)->firstOrFail();

        $result = app(DocumentSignatureService::class)->verify($doc);

        return response()->json($result);
    }

    public function download(SignedDocument $doc): \Symfony\Component\HttpFoundation\Response
    {
        abort_if($doc->doctor_id !== auth()->user()->doctor->id, 403);

        $content = Storage::disk('s3')->get($doc->storage_path);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receta_' . $doc->id . '.pdf"',
        ]);
    }
}