<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentFile extends Model
{
    protected $fillable = ['appointment_id','uploaded_by','original_name','path','mime','size'];

    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}