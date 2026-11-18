<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Online = 'online';
    case OnSite = 'on_site';
    case Free = 'free';       // total 0 (código promocional del 100 %): sin cobro, pero queda registrado

    public function label(): string
    {
        return match ($this) {
            self::Online => 'En línea',
            self::OnSite => 'En consulta',
            self::Free => 'Sin cobro',
        };
    }
}
