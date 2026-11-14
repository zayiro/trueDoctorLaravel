<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientHistoryNote extends Model
{
    use HasFactory;

    // Constante con el límite máximo permitido por cita/historia
    public const MAX_NOTES_PER_HISTORY = 3;

    protected $fillable = [
        'patient_history_id',
        'doctor_id',
        'content',
        'note_type',
    ];

    /**
     * Atributos que deben ser casteados (encriptación clínica en reposo).
     */
    protected function casts(): array
    {
        return [
            'content' => 'encrypted',
        ];
    }

    /**
     * Reglas de ciclo de vida del modelo: las notas clínicas son inmutables una vez creadas.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            abort(403, 'Las notas clínicas extemporáneas son inmutables y no pueden ser editadas.');
        });

        static::deleting(function () {
            abort(403, 'Las notas clínicas extemporáneas no pueden ser eliminadas.');
        });
    }

    /**
     * Historia clínica a la que pertenece esta nota.
     */
    public function patientHistory(): BelongsTo
    {
        return $this->belongsTo(PatientHistory::class);
    }

    /**
     * Médico que registró la nota.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
