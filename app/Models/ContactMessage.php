<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'is_read', // Útil para tu panel de administración
        'replied_at',
        'replied_by',
        'reply_body',
    ];

    /**
     * Atributos con valores predeterminados.
     */
    protected $attributes = [
        'is_read' => false,
    ];

    /**
     * Conversiones de tipo.
     */
    protected $casts = [
        'replied_at' => 'datetime',
    ];
}
