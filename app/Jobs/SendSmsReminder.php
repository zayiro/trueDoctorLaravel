<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class SendSmsReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;
    protected $message;
    protected $context; // Contexto adicional para logging (ej: 'appointment_123')

    /**
     * Crear instancia del job
     *
     * @param string $phoneNumber Número de teléfono del destinatario
     * @param string $message Mensaje SMS a enviar
     * @param string $context Contexto para logging (opcional)
     */
    public function __construct(string $phoneNumber, string $message, string $context = '')
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Ejecutar el job
     */
    public function handle__(SmsService $smsService)
    {
        // Normalizar número de teléfono
        $normalizedPhone = $smsService->normalizePhoneNumber($this->phoneNumber);

        if (!$normalizedPhone) {
            Log::warning("Número de teléfono inválido: {$this->phoneNumber} {$this->context}");
            $this->fail(new \Exception("Invalid phone number: {$this->phoneNumber}"));
            return;
        }

        // Enviar con reintentos y capturar respuesta
        try {
            $response = $smsService->sendWithRetry($normalizedPhone, $this->message, 3);

            if (!$response) {
                throw new \Exception("Failed to send SMS after 3 attempts");
            }
        } catch (\Exception $e) {
            Log::error("Fallo enviando recordatorio SMS {$this->context}", [
                'reference' => $this->context,
                'phone'     => $normalizedPhone,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            $this->fail($e);
        }
    }

    public function handle(SmsService $smsService)
    {        
        $normalizedPhone = $smsService->normalizePhoneNumber($this->phoneNumber);

        if (!$normalizedPhone) {
            Log::warning("Número inválido: {$this->phoneNumber}");
            $this->fail(new \Exception("Invalid phone number"));
            return;
        }
        
        try {
            $response = $smsService->sendWithRetry($normalizedPhone, $this->message, 3);
            
            if (!$response) {
                throw new \Exception("Failed to send SMS");
            }
        } catch (\Exception $e) {
            Log::error("Error: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Manejo de fallos
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job falló {$this->context}: " . $exception->getMessage());
        // Aquí puedes notificar al admin o guardar en DB
    }
}