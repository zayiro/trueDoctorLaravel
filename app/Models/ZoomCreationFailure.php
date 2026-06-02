<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoomCreationFailure extends Model
{
    protected $fillable = ['appointment_id', 'attempts', 'status', 'last_error'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
