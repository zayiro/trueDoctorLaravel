<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReport extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_UPHELD = 'upheld';
    public const STATUS_REVERSED = 'reversed';

    /** Motivos que puede elegir quien reporta. */
    public const REASONS = [
        'offensive' => 'Lenguaje ofensivo o discriminatorio',
        'personal_info' => 'Muestra datos personales o de salud de otra persona',
        'false' => 'Información falsa o difamatoria',
        'not_real' => 'No corresponde a una atención real',
        'spam' => 'Spam o publicidad',
        'other' => 'Otro motivo',
    ];

    protected $fillable = ['review_id', 'user_id', 'reason', 'details', 'status', 'resolved_by', 'resolved_at', 'resolution_note'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /** Reseñas distintas con al menos un reporte pendiente (lo que ve el administrador como «por revisar»). */
    public static function pendingReviewsCount(): int
    {
        return (int) static::query()->pending()->distinct()->count('review_id');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? 'Otro motivo';
    }
}
