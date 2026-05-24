<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAnalysis extends Model
{
    use HasFactory;

    // 1. Definir la tabla si no sigue la regla estricta del plural en inglés
    protected $table = 'exam_analyses';

    // 2. Habilitar la asignación masiva de columnas para tu controlador
    protected $fillable = [
        'user_id',
        'customer_email',
        'file_path',
        'reason_type',
        'reason_custom',
        'payment_id',
        'payment_status',
        'price',
        'ai_result',
    ];

    // 3. Mutador crucial: Convierte automáticamente el JSON de la BD en un Array de PHP
    protected $casts = [
        'ai_result' => 'array',
        'price' => 'decimal:2',
    ];

    // 4. Relación: El análisis pertenece a un Paciente/Usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
