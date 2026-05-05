<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSetting extends Model
{
    protected $fillable = [
        'doctor_id', 
        'plan_id', 
        'accepts_online_payments', 
        'min_notice_hours', 
        'max_advance_days', 
        'requires_approval'
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
