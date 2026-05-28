<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\City;
use App\Models\Clinic;
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
     * Genera el archivo dinámico sitemap.xml para la indexación de Google y Bing.
     */
    public function generateSitemap()
    {
        // 1. Consultamos los especialistas independientes aprobados y activos
        $doctors = Doctor::select(['id', 'slug', 'updated_at']) 
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        // 🔒 ADICIÓN MULTI-TENANT: Consultamos los centros médicos institucionales aprobados y activos
        $clinics = Clinic::select(['id', 'slug', 'updated_at'])
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        // 2. Consultamos el catálogo dinámico de síntomas indexados
        $indexedSymptoms = IndexedSymptom::select(['id', 'slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get();
        // 3. Compilamos la vista XML pasando todas las entidades del SaaS
        $sitemapContent = view('seo.sitemap', compact(
            'doctors', 
            'clinics', 
            'indexedSymptoms'
        ))->render();

        // 4. Retornamos la respuesta forzando el formato nativo XML y caché de 1 hora
        return response($sitemapContent, 200, [
            'Content-Type'  => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff' // Protección contra sniffing de código
        ]);
    }
    
    /**
     * Vista principal del buscador global de opendoctor.online (Priorización por Plan Premium y Calificación).
     */
    public function index(Request $request)
    { 
        $specialties = Specialty::orderBy('name', 'asc')->get();
        $cities = City::orderBy('name', 'asc')->get();                        
        
        // 1. Iniciamos la consulta base sobre el modelo maestro Doctor sin JOINS conflictivos
        $searchQuery = Doctor::with([
            'user',
            'specialties',
            'clinics.addresses' => function ($query) use ($request) {
                $query->where('status', true)->where('type', 'physical')->with('city');
                if ($request->filled('city')) {
                    $query->whereHas('city', function ($q) use ($request) {
                        $q->where('slug', $request->city);
                    });
                }
            },
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
        // 🔒 SOLUCIÓN ANTI-SYNTAX ERROR: Rescatamos el precio del plan vía Subquery limpia
        ->addSelect(['plan_price' => function ($subQuery) {
            $subQuery->select('plans.price')
                ->from('plans')
                ->join('doctor_settings', 'plans.id', '=', 'doctor_settings.plan_id')
                ->whereColumn('doctor_settings.doctor_id', 'doctors.id')
                ->limit(1);
        }])
        ->where('doctors.active', true) 
        ->where('doctors.validation_status', 'approved');
        // El médico debe contar con servicios activos en el ecosistema (Sedes propias o de clínicas)
        $searchQuery->where(function ($mainFilter) {
            $mainFilter->whereHas('addresses.services', function ($query) {
                $query->where('services.active', true);
            })->orWhereHas('clinics.addresses.services', function ($query) {
                $query->where('services.active', true);
            });
        });
        
        // Filtro condicional por Especialidad Médica (Slug)
        $searchQuery->when($request->specialty, function ($query) use ($request) {
            $query->whereHas('specialties', function ($q) use ($request) {
                $q->where('slug', $request->specialty);
            });
        });
        
        // Filtro condicional por Búsqueda de Ciudad
        $searchQuery->when($request->city, function ($query) use ($request) {
            $query->where(function ($cityFilter) use ($request) {
                $cityFilter->whereHas('addresses.city', function ($q) use ($request) {
                    $q->where('slug', $request->city);
                })->orWhereHas('clinics.addresses.city', function ($q) use ($request) {
                    $q->where('slug', $request->city);
                });
            });
        });
        
        // 🔥 ORDENACIÓN ALGORÍTMICA TOTALMENTE COMPATIBLE CON MYSQL STRICT
        $doctors = $searchQuery->orderBy('plan_price', 'desc') // 1. Prioridad del Plan (Subquery)
            ->orderBy('doctors.rating', 'desc')                 // 2. Estrellas de Reputación
            ->orderBy('doctors.reviews_count', 'desc')           // 3. Volumen de Testimonios
            ->paginate(10);
        
        // Conservamos los filtros en los enlaces de la paginación de la vista
        $doctors->appends($request->all());

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
