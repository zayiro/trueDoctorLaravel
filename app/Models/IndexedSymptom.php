<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndexedSymptom extends Model
{
    use HasFactory;

    protected $table = 'indexed_symptoms';

    protected $fillable = [
        'search_query',
        'slug',
        'specialty_id',
        'seo_title',
        'seo_description',
        'urgency_level',
        'ai_advice',
        'search_count',
        'clinical_description',
        'common_causes',
        'alarm_signs',
        'risk_factors',
        'self_care_advice',
        'image_url'
    ];

    protected $casts = [
        'search_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Niveles considerados como derivación médica inmediata.
     */
    public const URGENCIAS_INMEDIATAS = ['Alta'];
 
    public const NIVELES_URGENCIA = ['Baja', 'Media', 'Alta'];
      
    public function esUrgente(): bool
    {
        return in_array($this->urgency_level, self::URGENCIAS_INMEDIATAS, true);
    }
 
    protected static function boot()
    {
        parent::boot();
 
        static::saving(function (IndexedSymptom $symptom) {
            if (empty($symptom->slug) && !empty($symptom->search_query)) {
                $symptom->slug = Str::slug($symptom->search_query);
            }
        });
    }

    /**
     * ESTA ES LA FUNCIÓN QUE FALTA:
     * Un síntoma indexado pertenece a una única especialidad médica.
     */
    public function specialty()
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }
}
