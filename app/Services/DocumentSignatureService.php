<?php

// app/Services/DocumentSignatureService.php
namespace App\Services;

use App\Models\Doctor;
use App\Models\SignedDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DocumentSignatureService
{
    /**
     * Genera el PDF, lo firma con hash+timestamp y lo persiste.
     */
    public function sign(
        string $view,
        array  $data,
        Doctor $doctor,
        int    $patientId,
        string $type,
        object $signable,
        string $ip
    ): SignedDocument {

        // 1. Renderizar PDF
        $pdf     = Pdf::loadView($view, $data);
        $content = $pdf->output();

        // 2. Hash del documento (SHA-256 del contenido puro)
        $documentHash = hash('sha256', $content);

        // 3. Timestamp inmutable
        $signedAt = now();

        // 4. Firma = SHA-256 del hash + datos del firmante + secret de la app
        //    Si alguien altera el PDF, el hash no coincidirá.
        //    Si alguien altera los metadatos, la firma no coincidirá.
        $signatureHash = hash_hmac('sha256',
            implode('|', [
                $documentHash,
                $doctor->id,
                $doctor->medical_license,
                $patientId,
                $signedAt->toIso8601String(),
                $type,
            ]),
            config('app.key')  // Secret de Laravel como clave HMAC
        );

        // 5. Guardar PDF original en S3
        $path = "documents/{$type}/{$signable->id}_{$signedAt->timestamp}.pdf";
        Storage::disk('s3')->put($path, $content);

        // 6. Persistir registro de firma
        return SignedDocument::create([
            'signable_type'  => get_class($signable),
            'signable_id'    => $signable->id,
            'doctor_id'      => $doctor->id,
            'patient_id'     => $patientId,
            'type'           => $type,
            'document_hash'  => $documentHash,
            'signature_hash' => $signatureHash,
            'storage_path'   => $path,
            'signed_at'      => $signedAt,
            'signed_by_ip'   => $ip,
        ]);
    }

    /**
     * Verifica que un documento no fue alterado desde su firma.
     */
    public function verify(SignedDocument $signedDoc): array
    {
        $content      = Storage::disk('s3')->get($signedDoc->storage_path);
        $currentHash  = hash('sha256', $content);

        // Rehash con los mismos datos originales
        $expectedSignature = hash_hmac('sha256',
            implode('|', [
                $signedDoc->document_hash,
                $signedDoc->doctor_id,
                $signedDoc->doctor->medical_license,
                $signedDoc->patient_id,
                $signedDoc->signed_at->toIso8601String(),
                $signedDoc->type,
            ]),
            config('app.key')
        );

        $documentIntact   = hash_equals($currentHash, $signedDoc->document_hash);
        $signatureValid   = hash_equals($expectedSignature, $signedDoc->signature_hash);

        return [
            'valid'            => $documentIntact && $signatureValid,
            'document_intact'  => $documentIntact,
            'signature_valid'  => $signatureValid,
            'signed_at'        => $signedDoc->signed_at->format('d/m/Y H:i:s'),
            'signed_by'        => $signedDoc->doctor->user->name,
            'medical_license'  => $signedDoc->doctor->medical_license,
            'document_hash'    => $signedDoc->document_hash,
            'signature_hash'   => $signedDoc->signature_hash,
        ];
    }
}