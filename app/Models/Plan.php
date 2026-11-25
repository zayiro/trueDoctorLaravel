<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'plan',
        'applicable_role',
        'max_addresses',
        'max_services',
        'max_doctors',
        'max_appointments_per_year',
        'can_search_patients',
        'can_see_whatsapp_contact_button',
        'max_patients_list',
        'can_export_history',
        'has_telemedicine',
        'ai_scribe_enabled',
        'price',
        'duration_days',
        'active',
    ];

    protected $casts = [
        'can_search_patients' => 'boolean',
        'can_see_whatsapp_contact_button' => 'boolean',
        'can_export_history' => 'boolean',
        'has_telemedicine' => 'boolean',
        'ai_scribe_enabled' => 'boolean',
        'active' => 'boolean',
        'price' => 'decimal:2',
        'duration_days' => 'integer',
    ];

    /** Días de acceso que compra un pago de este plan (por defecto 30). */
    public function durationDays(): int
    {
        return max(1, (int) ($this->duration_days ?: 30));
    }

    /** «12 meses», «1 mes», «45 días». */
    public function durationLabel(): string
    {
        $d = $this->durationDays();
        if ($d >= 360 && $d <= 370) {
            return '12 meses';
        }
        if ($d % 30 === 0) {
            $months = intdiv($d, 30);

            return $months === 1 ? '1 mes' : "{$months} meses";
        }

        return "{$d} días";
    }

    /** Lo que cuesta al mes (precio ÷ meses de acceso). */
    public function perMonth(): float
    {
        $d = $this->durationDays();
        // Un plan anual (360-370 días) son 12 meses exactos; si no, días ÷ 30.
        $months = ($d >= 360 && $d <= 370) ? 12 : max(1, $d / 30);

        return (float) $this->price / $months;
    }
}
