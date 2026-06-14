<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Doctor;

class SymptomDirectoryController extends Controller
{
        /**
     * Muestra el índice general y catálogo público de síntomas.
     * Actúa como Hub Pilar de enlazado interno para SEO de conversión.
     */
    public function index()
    {
        // 🛡️ CORRECCIÓN: Se cambia el operador '::' por '.' para la sintaxis SQL estándar de Laravel
        $symptoms = DB::table('indexed_symptoms')
            ->leftJoin('specialties', 'indexed_symptoms.specialty_id', '=', 'specialties.id')
            ->select(
                'indexed_symptoms.id',
                'indexed_symptoms.search_query',
                'indexed_symptoms.slug',
                'indexed_symptoms.urgency_level',
                'specialties.name as specialty_name'
            )
            ->orderBy('indexed_symptoms.search_count', 'desc')
            ->paginate(24);

        return view('symptom.index', [
            'symptoms' => $symptoms,
            'metaTitle' => 'Guía de Orientación Médica por Síntomas | opendoctor.online',
            'metaDesc' => 'Analiza tus síntomas con nuestro asistente clínico automatizado. Conoce tu nivel de urgencia y agenda citas presenciales o virtuales en minutos.'
        ]);
    }

    /**
     * Renderiza la landing page automatica basada en el registro exacto de indexed_symptoms.
     * Mapea el sintoma con su especialidad y lista los medicos (particulares y staff de clinicas).
     */
    public function show(string $slug)
    {
        $symptom = DB::table('indexed_symptoms')->where('slug', $slug)->first();

        if (!$symptom) {
            abort(404, 'La guía de orientación médica para este síntoma no se encuentra disponible.');
        }

        $doctors = collect();
        
        // 🛡️ DECLARACIÓN INICIAL: Evita que Laravel la marque como indefinida
        $specialtyData = null; 

        if ($symptom->specialty_id) {
            $specialtyData = DB::table('specialties')
                ->where('id', $symptom->specialty_id)
                ->select('name', 'slug')
                ->first();                

            $doctors = Doctor::where('validation_status', 'approved')
                ->where('active', true)
                ->whereHas('specialties', function ($query) use ($symptom) {
                    $query->where('specialties.id', $symptom->specialty_id); 
                })
                ->with(['user', 'specialties', 'addresses' => function($q) {
                    $q->where('status', true)->with('city');
                }])
                ->paginate(12);
        }

        DB::table('indexed_symptoms')->where('id', $symptom->id)->increment('search_count');

        return view('symptom.landing', [
            'symptom'        => $symptom,
            'doctors'        => $doctors,
            'specialtyData'  => $specialtyData, // 🛡️ ENVIADA DE FORMA SEGURA
            'title'          => $symptom->search_query,
            'metaTitle'      => $symptom->seo_title ?? $symptom->search_query,
            'metaDesc'       => $symptom->seo_description,
            'urgency'        => $symptom->urgency_level,
            'recommendation' => $symptom->ai_advice,
        ]);
    }
}
