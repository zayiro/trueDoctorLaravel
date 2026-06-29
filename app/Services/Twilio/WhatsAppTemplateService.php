<?php

namespace App\Services\Twilio;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppTemplateService
{
    protected Client $client;
    protected string $from;

    // Content SIDs de tus plantillas aprobadas en Twilio
    // Cárgalos desde .env para no hardcodearlos
    protected array $templates = [
        'appointment_confirmed'  => '',
        'appointment_cancelled'  => '',
        'appointment_rescheduled' => '',
    ];

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $this->from = 'whatsapp:' . config('services.twilio.whatsapp_from');

        $this->templates = [
            'appointment_confirmed'   => config('services.twilio.templates.confirmed'),
            'appointment_cancelled'   => config('services.twilio.templates.cancelled'),
            'appointment_rescheduled' => config('services.twilio.templates.rescheduled'),
        ];
    }

    /**
     * Cita confirmada.
     * Plantilla: "Hola, {{1}}, su cita está confirmada. Sede: {{2}} | Hora: {{3}} | Doctor(a): {{4}}"
     */
    public function sendConfirmed(
        string $phone,
        string $patientName,
        string $sede,
        string $time,
        string $doctor
    ): bool {
        return $this->send($phone, 'appointment_confirmed', [
            '1' => $patientName,
            '2' => $sede,
            '3' => $time,
            '4' => $doctor,
        ]);
    }

    /**
     * Cita cancelada.
     * Plantilla: "Hola, {{1}}, lamentamos informarte que tu cita del {{2}} a las {{3}} con el Dr(a). {{4}} ha sido cancelada. Para reagendar comunícate con nosotros."
     */
    public function sendCancelled(
        string $phone,
        string $patientName,
        string $date,
        string $time,
        string $doctor
    ): bool {
        return $this->send($phone, 'appointment_cancelled', [
            '1' => $patientName,
            '2' => $date,
            '3' => $time,
            '4' => $doctor,
        ]);
    }

    /**
     * Cita reagendada.
     * Plantilla: "Hola, {{1}}, tu cita ha sido reagendada. Nueva fecha: {{2}} | Hora: {{3}} | Sede: {{4}} | Doctor(a): {{5}}"
     */
    public function sendRescheduled(
        string $phone,
        string $patientName,
        string $newDate,
        string $newTime,
        string $sede,
        string $doctor
    ): bool {
        return $this->send($phone, 'appointment_rescheduled', [
            '1' => $patientName,
            '2' => $newDate,
            '3' => $newTime,
            '4' => $sede,
            '5' => $doctor,
        ]);
    }

    /**
     * Recordatorio de cita.
     * Plantilla: "Hola {{1}}, te recordamos tu cita médica el {{2}} a las {{3}} con Dr(a). {{4}}. ¡Te esperamos!"
     */
    public function sendReminder(
        string $phone,
        string $patientName,
        string $date,
        string $time,
        string $doctor
    ): bool {
        return $this->send($phone, 'appointment_reminder', [
            '1' => $patientName,
            '2' => $date,
            '3' => $time,
            '4' => $doctor,
        ]);
    }

    /**
     * Método base que envía cualquier plantilla.
     */
    protected function send(string $phone, string $templateKey, array $variables): bool
    {
        $contentSid = $this->templates[$templateKey] ?? null;

        if (!$contentSid) {
            Log::error("WhatsAppTemplateService: SID no configurado para [{$templateKey}]");
            return false;
        }

        $toPhone = 'whatsapp:' . $this->normalizePhone($phone);

        try {
            $this->client->messages->create($toPhone, [
                'from'             => $this->from,
                'contentSid'       => $contentSid,
                'contentVariables' => json_encode($variables),
            ]);

            Log::info("WhatsApp [{$templateKey}] enviado a {$toPhone}");
            return true;

        } catch (Exception $e) {
            Log::error("WhatsApp [{$templateKey}] falló para {$toPhone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normaliza el teléfono al formato E.164 (+57XXXXXXXXXX).
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        // Si viene sin código de país, asume Colombia
        if (strlen($phone) === 10) {
            $phone = '57' . $phone;
        }

        return '+' . ltrim($phone, '+');
    }
}