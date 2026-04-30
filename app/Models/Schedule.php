<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['address_id', 'day', 'start_time', 'end_time', 'duration'];

    public function address() 
    {
        return $this->belongsTo(Address::class);
    }

}
