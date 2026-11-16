<?php

namespace App\Models;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MedicalAnalysis extends Model
{
    protected $fillable = [
        'file_paths',
        'exam_type', 
        'customer_email',
        'reason_type',
        'reason_custom',
        'promo_code',
        'price',
        'status',
        'total_images_uploaded', 
        'processed_images_count', 
        'decimation_factor', 
        'payment_status',
        'access_token',
        'payment_id',
        'wompi_transaction_id',
        'ai_response',
        'ai_provider',
        'analysis_language',
        'result_email_sent_at'
    ];

    protected $table = 'medical_analyses';

    // 3. Mutador crucial: Convierte automáticamente el JSON de la BD en un Array de PHP
    protected $casts = [
        'ai_response' => 'array',
        'price'       => 'decimal:2',
        'file_paths'  => 'array', 
    ];

    /**
     * Hook que se ejecuta al crear un nuevo registro: genera automáticamente
     * un token público impredecible para usar en la URL en vez del ID.
     */
    protected static function booted(): void
    {
        static::creating(function (MedicalAnalysis $analysis) {
            if (empty($analysis->access_token)) {
                $analysis->access_token = self::generateUniqueAccessToken();
            }
        });
    }
 
    /**
     * Genera un token único de 48 caracteres (suficientemente largo para
     * que sea inviable de adivinar por fuerza bruta).
     */
    public static function generateUniqueAccessToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('access_token', $token)->exists());
 
        return $token;
    }
 
    /**
     * Permite que Laravel resuelva el modelo en las rutas usando access_token
     * en vez del ID, vía Route Model Binding. Ver Route::get(...)->scopeBindings()
     * o el método getRouteKeyName() de abajo.
     */
    public function getRouteKeyName(): string
    {
        return 'access_token';
    }

    /**
     * Especialidad que sugirió la IA y a dónde mandar a agendar.
     * 'target' es la sugerida si tiene médicos (status = 1); si no, el fallback.
     */
    public function specialtyRecommendation(): array
    {
        $slug = $this->ai_response['especialidad_slug'] ?? null;

        $suggested = $slug ? Specialty::where('slug', $slug)->first() : null;

        if ($suggested && (int) $suggested->status === 1) {
            return ['suggested' => $suggested, 'target' => $suggested, 'available' => true];
        }

        $fallback = Specialty::where('slug', config('ai_specialties.fallback', 'medicina-general'))
            ->where('status', 1)
            ->first();

        return ['suggested' => $suggested, 'target' => $fallback, 'available' => false];
    }
}