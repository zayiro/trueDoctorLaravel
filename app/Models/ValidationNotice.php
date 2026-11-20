<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de cada aviso enviado a un médico o clínica para que corrija su documentación.
 */
class ValidationNotice extends Model
{
    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'sent_by',
        'recipient_email',
        'items',
        'note',
        'sent_at',
    ];

    protected $casts = [
        'items'   => 'array',
        'sent_at' => 'datetime',
    ];
}
