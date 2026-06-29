<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GalleryImage extends Model
{
    protected $fillable = [
        'galleryable_id',
        'galleryable_type',
        'path',
        'caption',
        'order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function galleryable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}