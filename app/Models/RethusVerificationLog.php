<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bitácora de la verificación ReTHUS de un doctor (quién, cuándo y qué se confirmó). */
class RethusVerificationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['doctor_id', 'action', 'by_user_id', 'confirmed_text', 'note'];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
