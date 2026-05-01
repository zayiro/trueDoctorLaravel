<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\City;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $specialties = Specialty::orderBy('name', 'asc')->get();
        $cities = City::orderBy('name', 'asc')->get();
                        
        $doctors = Doctor::with(['user', 'specialties', 'addresses.city'])
        // Filtramos por la relación Muchos a Muchos
        ->whereHas('specialties', function($q) use ($request) {
            $q->where('slug', $request->specialty);
        })
        // Filtramos por la ciudad en sus direcciones
        ->whereHas('addresses.city', function($q) use ($request) {
            $q->where('slug', $request->city);
        })
        ->paginate(10);

        $doctors->appends($request->all());

        //dd($doctors);

        return view('search.index', compact('doctors', 'specialties', 'cities'));
    }

    public function search(Request $request)
    {
        // 1. Validar que ambos campos existan
        $request->validate([
            'specialty' => 'required',
            'city' => 'required',
        ], [
            'specialty.required' => 'Por favor, selecciona una especialidad.',
            'city.required' => 'Debes elegir una ciudad para buscar.',
        ]);

        // 2. Construir la consulta con relaciones
        $doctors = Doctor::with(['user', 'specialties', 'addresses.city'])
        // Filtramos por la relación Muchos a Muchos
        ->whereHas('specialties', function($q) use ($request) {
            $q->where('slug', $request->specialty);
        })
        // Filtramos por la ciudad en sus direcciones
        ->whereHas('addresses.city', function($q) use ($request) {
            $q->where('slug', $request->city);
        })
        ->paginate(10);

        // 3. Mantener los parámetros en la paginación
        $doctors->appends($request->all());

        return view('search.search-results', compact('doctors'));
    }
}
