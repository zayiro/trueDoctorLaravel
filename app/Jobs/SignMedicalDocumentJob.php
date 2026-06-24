<?php

namespace App\Jobs;

use App\Models\SignedDocument;
use App\Services\CerticamaraSignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SignMedicalDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(private SignedDocument $document) {}

    public function handle(CerticamaraSignatureService $certicamara): void
    {
        try {
            $result = $certicamara->signDocument(
                $this->document->storage_path,
                [
                    'reason'       => 'Receta médica electrónica - opendoctor.online',
                    'doctor_name'  => $this->document->doctor->user->name,
                    'doctor_email' => $this->document->doctor->user->email,
                    'location'     => $this->document->doctor->addresses->first()?->city?->name ?? 'Colombia',
                ]
            );

            $this->document->update([
                'status'                    => 'signed',
                'signed_storage_path'       => $result['signed_path'],
                'signed_hash'               => $result['signed_hash'],
                'certicamara_transaction_id'=> $result['transaction_id'],
                'signed_at'                 => now(),
            ]);

        } catch (\Exception $e) {
            $this->document->update(['status' => 'failed']);
            throw $e;
        }
    }
}