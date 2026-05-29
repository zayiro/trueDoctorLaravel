<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PatientHistoryAttachment extends Model
{
    protected $fillable = ['patient_id', 'name', 'file_path', 'file_type', 'file_size'];

    public function history()
    {
        return $this->belongsTo(PatientHistory::class, 'patient_id');
    }

    // Accesor para obtener la URL segura del archivo temporal o privado
    public function getUrlAttribute()
    {
        return Storage::disk('protected')->url($this->file_path);
    }
}
