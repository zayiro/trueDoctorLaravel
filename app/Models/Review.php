<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN    = 'hidden';

    protected $fillable = [
        'user_id',
        'rating',
        'comment',
        'status',
        'reviewable_id',
        'reviewable_type',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /** Nombre público: "María Gómez Pérez" => "María G." (requiere cargar la relación user). */
    protected function authorName(): Attribute
    {
        return Attribute::get(fn () => self::publicAuthorName($this->user?->name ?? ''));
    }

    public static function publicAuthorName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) < 2) {
            return $parts[0] ?? 'Paciente';
        }

        return $parts[0] . ' ' . mb_strtoupper(mb_substr($parts[1], 0, 1)) . '.';
    }
}