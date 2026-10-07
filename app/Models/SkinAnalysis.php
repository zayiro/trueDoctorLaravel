<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Análisis de piel con IA. Tabla propia: skin_analyses.
 */
class SkinAnalysis extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAYMENT_FAILED = 'payment_failed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ERROR = 'error';

    /** Estados desde los que todavía se puede pagar. */
    public const PAYABLE_STATUSES = [self::STATUS_PENDING_PAYMENT, self::STATUS_PAYMENT_FAILED];

    /** Carpeta (en el disco privado) donde se guardan las fotos de cada análisis. */
    public const STORAGE_DIR = 'skin-analysis/analyses';

    protected $table = 'skin_analyses';

    protected $fillable = [
        'customer_email',
        'analysis_language',
        'body_location',
        'description',
        'status',
        'price',
        'discount_amount',
        'wompi_fee',
        'total_amount',
        'promo_code',
        'wompi_transaction_id',
        'paid_at',
        'ai_model',
        'report',
        'failure_reason',
        'completed_at',
    ];

    protected $casts = [
        'report' => 'array',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SkinAnalysis $analysis) {
            if (empty($analysis->access_token)) {
                $analysis->access_token = self::generateUniqueAccessToken();
            }
        });
    }

    public static function generateUniqueAccessToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('access_token', $token)->exists());

        return $token;
    }

    public function getRouteKeyName(): string
    {
        return 'access_token';
    }

    /** Directorio de las fotos de este análisis en el disco privado. */
    public function imageDirectory(): string
    {
        return self::STORAGE_DIR . '/' . $this->id;
    }
}