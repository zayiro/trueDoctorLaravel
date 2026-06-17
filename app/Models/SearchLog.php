<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchLog extends Model
{
    // Campos habilitados en inglés para asignación masiva
    protected $fillable = [
        'specialty', 
        'city', 
        'country', 
        'ip_address'
    ];

    /**
     * Scope para obtener el ranking de especialidades más buscadas.
     */
    public function scopeTopSpecialties(Builder $query, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $query->select('specialty', DB::raw('count(*) as total_searches'))
            ->groupBy('specialty')
            ->orderByDesc('total_searches')
            ->limit($limit)
            ->get();
    }

    /**
     * Scope para obtener el ranking de ciudades más buscadas.
     */
    public function scopeTopCities(Builder $query, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $query->select('city', DB::raw('count(*) as total_searches'))
            ->groupBy('city')
            ->orderByDesc('total_searches')
            ->limit($limit)
            ->get();
    }
}
