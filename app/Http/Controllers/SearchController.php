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
        
        $doctors = Doctor::with([
            'user',
            'specialties',
            'addresses.services',
            'addresses' => function ($query) use ($request) {
                // 1. Filtramos las direcciones por ciudad si el usuario seleccionó una
                if ($request->filled('city')) {
                    $query->whereHas('city', function ($q) use ($request) {
                        $q->where('slug', $request->city);
                    });
                }
                // 2. Cargar los servicios de cada sede
                $query->with('services'); 
            },
            'addresses.city' // Para mostrar el nombre de la ciudad en la vista
        ])
        // Filtro: Solo doctores que tengan la especialidad solicitada
        ->when($request->specialty, function ($query) use ($request) {
            $query->whereHas('specialties', function ($q) use ($request) {
                $q->where('slug', $request->specialty);
            });
        })
        // Filtro: Solo doctores que tengan direcciones en la ciudad solicitada
        ->when($request->city, function ($query) use ($request) {
            $query->whereHas('addresses.city', function ($q) use ($request) {
                $q->where('slug', $request->city);
            });
        })
        ->paginate(10);

        // Mantener los filtros en los links de paginación
        $doctors->appends($request->all());

        return view('search.index', compact('doctors', 'specialties', 'cities'));
    }

    public function search(Request $request)
    {                
        $request->validate([
            'specialty' => 'required',
        ], [
            'specialty.required' => 'Por favor, selecciona una especialidad.',
        ]);
        
        $specialties = Specialty::orderBy('name', 'asc')->get();
        $cities = City::orderBy('name', 'asc')->get();                        
        
        $doctors = Doctor::with([
            'user',
            'specialties',
            'addresses.services',
            'addresses' => function ($query) use ($request) {
                // 1. Filtramos las direcciones por ciudad si el usuario seleccionó una
                if ($request->filled('city')) {
                    $query->whereHas('city', function ($q) use ($request) {
                        $q->where('slug', $request->city);
                    });
                }
                // 2. Cargar los servicios de cada sede
                $query->with('services'); 
            },
            'addresses.city' // Para mostrar el nombre de la ciudad en la vista
        ])
        // Filtro: Solo doctores que tengan la especialidad solicitada
        ->when($request->specialty, function ($query) use ($request) {
            $query->whereHas('specialties', function ($q) use ($request) {
                $q->where('slug', $request->specialty);
            });
        })
        // Filtro: Solo doctores que tengan direcciones en la ciudad solicitada
        ->when($request->city, function ($query) use ($request) {
            $query->whereHas('addresses.city', function ($q) use ($request) {
                $q->where('slug', $request->city);
            });
        })
        ->paginate(10);

        // Mantener los filtros en los links de paginación
        $doctors->appends($request->all());

        //dd($doctors);

        return view('search.search-results', compact('doctors'));
    }
}
