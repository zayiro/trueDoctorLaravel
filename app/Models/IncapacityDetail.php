<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Datos propios de una incapacidad médica (el documento en sí es un Certificate de tipo «incapacity»).
 * La justificación de una incapacidad retroactiva se guarda cifrada.
 */
class IncapacityDetail extends Model
{
    protected $fillable = [
        'certificate_id', 'origin', 'service_group', 'modality',
        'start_date', 'end_date', 'days',
        'is_extension', 'previous_certificate_id', 'is_retroactive', 'retroactive_reason',
    ];

    protected $casts = [
        'start_date'         => 'date',
        'end_date'           => 'date',
        'days'               => 'integer',
        'is_extension'       => 'boolean',
        'is_retroactive'     => 'boolean',
        'retroactive_reason' => 'encrypted',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}
