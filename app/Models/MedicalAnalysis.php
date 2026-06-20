<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalAnalysis extends Model
{
    protected $fillable = [
        'cleaned_text', 
        'ai_response',
        'customer_email',
        'reason_type',
        'reason_custom',
        'status',
        'payment_id',
        'payment_status',
        'price',
    ];

    // 3. Mutador crucial: Convierte automáticamente el JSON de la BD en un Array de PHP
    protected $casts = [
        'ai_response' => 'array',
        'price' => 'decimal:2',
    ];
}
