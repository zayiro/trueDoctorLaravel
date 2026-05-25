<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\City;
use App\Models\IndexedSymptom;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    /**
     * Genera el archivo dinámico sitemap.xml para Google y Bing.
     */
    public function generateSitemap()
    {
        $doctors = Doctor::select(['id', 'slug', 'updated_at']) 
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        $indexedSymptoms = IndexedSymptom::select(['id', 'slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $content = view('seo.sitemap', compact('doctors', 'indexedSymptoms'))->render();

        return response($content, 200, [
            'Content-Type'  => 'application/xml',
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }
    
        /**
     * Vista principal del buscador global de opendoctor.online (Priorización por Plan Premium y Calificación).
     */
    public function index(Request $request)
    { 
        $specialties = Specialty::orderBy('name', 'asc')->get();
        $cities = City::orderBy('name', 'asc')->get();                        
        
        $doctors = Doctor::with([
            'user',
            'specialties',
            // Precarga avanzada de sedes institucionales de clínicas
            'clinics.addresses' => function ($query) use ($request) {
                $query->where('status', true)->where('type', 'physical')->with('city');
                if ($request->filled('city')) {
                    $query->whereHas('city', function ($q) use ($request) {
                        $q->where('slug', $request->city);
                    });
                }
            },
            // Precarga avanzada de sedes privadas del especialista autónomo
            'addresses.city',
            'addresses' => function ($query) use ($request) {
                $query->where('status', true);
                if ($request->filled('city')) {
                    $query->whereHas('city', function ($q) use ($request) {
                        $q->where('slug', $request->city);
                    });
                }
                $query->with(['services' => function ($q) {
                    $q->where('services.active', true);
                }]);
            }
        ])
        // 🔥 MEJORA SAAS: Conexión atómica de base de datos para leer el nivel del plan activo del médico
        ->join('doctor_settings', 'doctors.id', '=', 'doctor_settings.doctor_id')
        ->join('plans', 'doctor_settings.plan_id', '=', 'plans.id')
        
        // Seleccionamos todas las columnas del doctor y renombramos el precio del plan para ordenación
        ->select('doctors.*', 'plans.price as plan_price')
        
        ->where('doctors.active', true) 
        ->where('doctors.validation_status', 'approved') 
        
        // El médico debe contar con servicios activos (en clínicas o consulta privada)
        ->where(function ($mainQuery) {
            $mainQuery->whereHas('addresses.services', function ($query) {
                $query->where('services.active', true);
            })->orWhereHas('clinics.addresses.services', function ($query) {
                $query->where('services.active', true);
            });
        })
        
        // Filtro condicional por Especialidad Médica
        ->when($request->specialty, function ($query) use ($request) {
            $query->whereHas('specialties', function ($q) use ($request) {
                $q->where('slug', $request->specialty);
            });
        })
        
        // Búsqueda por Ciudad (Sedes Privadas OR Sedes de Clínicas vinculadas)
        ->when($request->city, function ($query) use ($request) {
            $query->where(function($mainQuery) use ($request) {
                $mainQuery->whereHas('addresses.city', function ($q) use ($request) {
                    $q->where('slug', $request->city);
                })->orWhereHas('clinics.addresses.city', function($q) use ($request) {
                    $q->where('slug', $request->city);
                });
            });
        })
        
        // 🔥 CRITERIOS DE ORDENAMIENTO REQUERIDOS:
        ->orderBy('plans.price', 'desc')      // 1. Los mejores planes (Premium/Profesionales) van de primero
        ->orderBy('doctors.rating', 'desc')    // 2. Desempate por mayor número de estrellas de calificación
        ->orderBy('doctors.reviews_count', 'desc') // 3. Desempate por volumen de testimonios de pacientes
        ->paginate(10);
        
        $doctors->appends($request->all());

        //dd($doctors);

        return view('search.index', compact('doctors', 'specialties', 'cities'));
    }

    /**
     * Despacha los resultados de coincidencia exacta de criterios.
     */
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
                if ($request->filled('city')) {
                    $query->whereHas('city', function ($q) use ($request) {
                        $q->where('slug', $request->city);
                    });
                }
                $query->with('services'); 
            },
            'addresses.city'
        ])
        ->where('active', true)
        ->where('validation_status', 'approved')
        ->when($request->specialty, function ($query) use ($request) {
            $query->whereHas('specialties', function ($q) use ($request) {
                $q->where('slug', $request->specialty);
            });
        })
        // Conserva el doble escaneo relacional por ciudad de la clínica o consulta autónoma
        ->when($request->city, function ($query) use ($request) {
            $query->where(function($mainQuery) use ($request) {
                $mainQuery->whereHas('addresses.city', function ($q) use ($request) {
                    $q->where('slug', $request->city);
                })->orWhereHas('clinics.addresses.city', function($q) use ($request) {
                    $q->where('slug', $request->city);
                });
            });
        })
        ->paginate(10);

        $doctors->appends($request->all());

        return view('search.search-results', compact('doctors', 'specialties', 'cities'));
    }

        /**
     * Muestra la vista de búsqueda interactiva por sintomatología.
     */
    public function searchSymptomView(Request $request)
    {
        return view('search.search-symptom');
    }

    /**
     * Procesa el síntoma de forma asíncrona mediante OpenAI y almacena en caché e IndexedSymptom.
     * Genera de manera automática estructuras semánticas e indexación orgánica en caliente.
     */
    public function searchBySymptom(Request $request): JsonResponse
    {        
        $queryStr = trim($request->input('symptom'));

        if (empty($queryStr) || strlen($queryStr) < 3) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'El síntoma debe tener al menos 3 caracteres.',
                'medicos' => []
            ], 400);
        }

        // 1. Obtener especialidades mapeadas por nombre
        $specialtiesMap = Specialty::pluck('id', 'name')->toArray(); 
        $stringEspecialidades = implode(', ', array_keys($specialtiesMap));

        $sintomaClave = 'triage_' . Str::slug($queryStr);

        try {
            // 2. Sistema de Inteligencia Artificial estructurada con caché de 24 horas
            $triage = Cache::remember($sintomaClave, 1440, function () use ($queryStr, $stringEspecialidades) {
                
                // 🔥 COMPLETADO Y BLINDADO: Estructura estricta de JSON Schema para OpenAI
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Eres un asistente médico experto de un SAAS de salud llamado opendoctor.online.'],
                        ['role' => 'user', 'content' => "Analiza este síntoma: '{$queryStr}'. Selecciona una especialidad de esta lista: {$stringEspecialidades}. Si ninguna encaja de forma directa, usa 'General'."]
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'triage_medico_saas',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'especialidad_correcta' => [
                                        'type' => 'string', 
                                        'description' => 'Nombre exacto de la especialidad tomada de la lista provista.'
                                    ],
                                    'especialidad_slug' => [
                                        'type' => 'string', 
                                        'description' => 'El slug de la especialidad elegida en minúsculas y separado por guiones.'
                                    ],
                                    'urgencia' => [
                                        'type' => 'string', 
                                        'enum' => ['Alta', 'Media', 'Baja'],
                                        'description' => 'Nivel de urgencia estimado para el cuadro clínico.'
                                    ],
                                    'consejo' => [
                                        'type' => 'string', 
                                        'description' => 'Breve orientación o recomendación preliminar al paciente.'
                                    ],
                                    'seo_title' => [
                                        'type' => 'string', 
                                        'description' => 'Título SEO optimizado para la landing automatizada de este síntoma.'
                                    ],
                                    'seo_description' => [
                                        'type' => 'string', 
                                        'description' => 'Meta-descripción SEO fluida e indexable para motores de búsqueda.'
                                    ]
                                ],
                                'required' => [
                                    'especialidad_correcta', 
                                    'especialidad_slug', 
                                    'urgencia', 
                                    'consejo', 
                                    'seo_title', 
                                    'seo_description'
                                ],
                                'additionalProperties' => false
                            ]
                        ]
                    ]
                ]);

                // Decodificamos el output estructurado de GPT
                return json_decode($response->choices[0]->message->content, true);
            });

            // 3. INDEXACIÓN AUTOMÁTICA (Persistencia SEO)
            // Si el síntoma es nuevo, lo guardamos en la tabla indexed_symptoms para autogenerar landings
            $slugSintoma = Str::slug($queryStr);
            IndexedSymptom::firstOrCreate(
                ['slug' => $slugSintoma],
                [
                    'name'            => ucfirst($queryStr),
                    'seo_title'       => $triage['seo_title'],
                    'seo_description' => $triage['seo_description'],
                    'urgency_level'   => $triage['urgencia'],
                    'advice'          => $triage['consejo'],
                ]
            );

            // 4. CONSULTA RELACIONAL DE MÉDICOS DISPONIBLES
            // Buscamos los médicos calificados en la especialidad sugerida por la IA
            $medicos = Doctor::with(['user', 'specialties', 'addresses.city'])
                ->where('active', true)
                ->where('validation_status', 'approved')
                ->whereHas('specialties', function ($q) use ($triage) {
                    $q->where('slug', $triage['especialidad_slug']);
                })
                ->take(6)
                ->get();

            return response()->json([
                'exito'         => true,
                'triage'        => $triage,
                'medicos_count' => $medicos->count(),
                'medicos'       => $medicos
            ]);

        } catch (\Exception $e) {
            Log::error("Fallo crítico en el motor de triage por IA: " . $e->getMessage());
            
            return response()->json([
                'exito'   => false,
                'mensaje' => 'No pudimos procesar tu diagnóstico por síntomas en este momento. Por favor, selecciona una especialidad manualmente.',
                'medicos' => []
            ], 500);
        }
    }
}
