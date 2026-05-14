<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\City;
use App\Models\MedicalExpertise;
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

        return view('search.search-results', compact('doctors'));
    }

    public function searchBySymptom(Request $request)
    {        
        // Validamos que el usuario haya escrito algo en el buscador del menubar
        $queryStr = $request->input('search');

        if (empty($queryStr)) {
            return redirect()->route('medicos.index'); // Redirecciona al index limpio si está vacío
        }

        // Cargamos los catálogos necesarios para los filtros laterales de la vista
        $specialties = Specialty::orderBy('name', 'asc')->get();
        $cities = City::orderBy('name', 'asc')->get();                        
        
        // Ejecutamos la consulta con la misma estructura que tu index
        $doctors = Doctor::with([
            'user',
            'specialties',
            'addresses.services',
            'addresses',
            'addresses.city'
        ])
        // Filtro crítico: Buscamos en la relación de enfermedades/síntomas
        ->whereHas('expertises', function ($query) use ($queryStr) {
            $query->whereFullText(['disease_name', 'symptoms_keywords'], $queryStr);
            
            // Alternativa tradicional por si no usas índices FullText:
            // $query->where('disease_name', 'LIKE', "%{$queryStr}%")
            //       ->orWhere('symptoms_keywords', 'LIKE', "%{$queryStr}%");
        })
        ->paginate(10);

        // Mantener el término de búsqueda en los enlaces de la paginación (Páginas 2, 3, etc.)
        $doctors->appends(['search' => $queryStr]);

        // Retornamos la misma vista para que reutilices toda tu interfaz visual de resultados
        return view('search.index', compact('doctors', 'specialties', 'cities'));
    }
}
