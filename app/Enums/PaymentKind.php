<?php

namespace App\Enums;

enum PaymentKind: string
{
    case Appointment  = 'appointment';
    case Subscription = 'subscription';
    case Exam         = 'exam';
    case SkinAnalysis = 'skin_analysis';

    public function label(): string
    {
        return match ($this) {
            self::Appointment  => 'Cita médica',
            self::Subscription => 'Suscripción de plan',
            self::Exam         => 'Análisis de exámenes',
            self::SkinAnalysis => 'Análisis de piel',
        };
    }

    /** Módulo de códigos promocionales equivalente (PromoCode::MODULE_*), si aplica. */
    public function promoModule(): ?string
    {
        return match ($this) {
            self::Appointment  => 'appointment',
            self::Exam         => 'exam',
            self::SkinAnalysis => 'skin_analysis',
            self::Subscription => null,
        };
    }
}
