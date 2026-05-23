<?php

namespace App\Http\Controllers\Admin;

use App\Models\Doctor;
use App\Models\IndexedSymptom;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;


class AdminController extends Controller
{
    public function __construct(){}

    public function listIndexedSymptoms(Request $request)
    {
        // 1. Consulta optimizada ordenando por especialidad y luego por los más nuevos
        $symptoms = IndexedSymptom::select(['id', 'original_query', 'slug', 'specialty_suggested', 'created_at'])
            ->orderBy('specialty_suggested', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(15) // Paginación de 15 elementos por página
            ->withQueryString(); // Mantiene filtros si decides agregarlos luego

        // 2. Contador total para medir el avance del SEO Programático
        $totalSymptoms = IndexedSymptom::count();

        // 3. Retornamos la vista enviando los datos
        return view('admin.symptoms.index', compact('symptoms', 'totalSymptoms'));
    }

    public function clearCache()
    {        
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('permission:cache-reset');
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cache eliminada correctamente.'
        ]);
    }
}
