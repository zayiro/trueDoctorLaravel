<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalExpertise extends Model
{
    protected $fillable = ['doctor_id', 'disease_name', 'symptoms_keywords'];

    public function doctor() 
    {
        return $this->belongsTo(Doctor::class);
    }
}
