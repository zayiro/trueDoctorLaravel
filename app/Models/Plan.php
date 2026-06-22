<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'plan',
        'applicable_role',
        'max_addresses',
        'max_services',
        'max_doctors',
        'max_appointments_per_year',
        'can_search_patients',
        'can_see_whatsapp_contact_button',
        'max_patients_list',
        'can_export_history',
        'has_telemedicine',
        'ai_scribe_enabled',
        'price',
        'active',
    ];

    protected $casts = [
        'can_search_patients' => 'boolean',
        'can_see_whatsapp_contact_button' => 'boolean',
        'can_export_history' => 'boolean',
        'has_telemedicine' => 'boolean',
        'ai_scribe_enabled' => 'boolean',
        'active' => 'boolean',
        'price' => 'decimal:2',
    ];
}
