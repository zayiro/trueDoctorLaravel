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
        'search_count'
    ];

    /**
     * ESTA ES LA FUNCIÓN QUE FALTA:
     * Un síntoma indexado pertenece a una única especialidad médica.
     */
    public function specialty()
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }
}
