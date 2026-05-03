<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = ['doctor_id', 'title', 'slug', 'content', 'active'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function getPublicUrlAttribute()
    {
        return route('landing.public', [
            'doctor_slug' => $this->doctor->slug,
            'campaign_slug' => $this->slug
        ]);
    }
}
