<?php

// app/Models/SignedDocument.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignedDocument extends Model
{
    protected $fillable = [
        'signable_type', 'signable_id',
        'doctor_id', 'patient_id', 'type',
        'document_hash', 'signature_hash',
        'storage_path', 'signed_at', 'signed_by_ip',
        'status',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function signable()
    {
        return $this->morphTo();
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}