<?php

namespace App\Enums;

enum PaymentKind: string
{
    case Appointment  = 'appointment';
    case Subscription = 'subscription';
    case Exam         = 'exam';
    case SkinAnalysis = 'skin_analysis';
    case AiChat       = 'ai_chat';

    public function label(): string
    {
        return match ($this) {
            self::Appointment  => 'Cita médica',
            self::Subscription => 'Suscripción de plan',
            self::Exam         => 'Análisis de exámenes',
            self::SkinAnalysis => 'Análisis de piel',
            self::AiChat       => 'Chat de Salud con IA',
        };
    }

    /** Módulo de códigos promocionales equivalente (PromoCode::MODULE_*), si aplica. */
    public function promoModule(): ?string
    {
        return match ($this) {
            self::Appointment  => 'appointment',
            self::Exam         => 'exam',
            self::SkinAnalysis => 'skin_analysis',
            self::AiChat       => null,
            self::Subscription => null,
        };
    }
}
