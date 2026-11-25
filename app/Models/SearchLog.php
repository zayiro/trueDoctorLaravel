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
        $rows = $query->select('specialty', DB::raw('count(*) as total_searches'))
            ->whereNotNull('specialty')
            ->where('specialty', '!=', '')
            ->groupBy('specialty')
            ->orderByDesc('total_searches')
            ->limit($limit)
            ->get();

        // paneltendencias: nombre real (con tildes) tomado de la tabla specialties; si la especialidad ya no existe, la vista usa el slug.
        $names = Specialty::whereIn('slug', $rows->pluck('specialty'))->pluck('name', 'slug');
        foreach ($rows as $row) {
            $row->specialty_name = $names[$row->specialty] ?? null;
        }

        return $rows;
    }

    /**
     * Scope para obtener el ranking de ciudades más buscadas.
     * metricabusquedas: no cuenta las búsquedas sin ciudad resuelta ('unknown').
     */
    public function scopeTopCities(Builder $query, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $query->select('city', DB::raw('count(*) as total_searches'))
            ->where('city', '!=', 'unknown')
            ->groupBy('city')
            ->orderByDesc('total_searches')
            ->limit($limit)
            ->get();
    }
}
