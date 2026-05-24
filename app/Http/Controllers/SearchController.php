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
     * Genera el archivo dinámico sitemap.xml para los motores de búsqueda.
    */
    public function generateSitemap()
    {
        // 1. Extraemos médicos activos cargando solo columnas necesarias
        $doctors = Doctor::select(['id', 'slug', 'updated_at']) 
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        // 2. CORRECCIÓN: Cambiamos $symptoms por $indexedSymptoms para tu vista Blade
        $indexedSymptoms = IndexedSymptom::select(['id', 'slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 3. Renderizamos la plantilla XML pasando los nombres exactos de las variables
        $content = view('seo.sitemap', compact('doctors', 'indexedSymptoms'))->render();

        // 4. Retornamos la respuesta XML con caché de 1 hora
        return response($content, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }
    
    public function index(Request $request)
    {        
        $specialties = Specialty::orderBy('name', 'asc')->get();
        $cities = City::orderBy('name', 'asc')->get();                        
        
        $doctors = Doctor::with([
            'user',
            'specialties',
            'addresses.city',
            'addresses' => function ($query) use ($request) {
                // 1. Filtrar sedes por ciudad si se solicita
                if ($request->filled('city')) {
                    $query->whereHas('city', function ($q) use ($request) {
                        $q->where('slug', $request->city);
                    });
                }
                // 2. Cargar SOLO los servicios que estén activos en la vista
                $query->with(['services' => function ($q) {
                    $q->where('services.active', true);
                }]);
            }
        ])
        // CONDICIONES CRÍTICAS DEL SAAS:
        ->where('active', true) 
        ->where('validation_status', 'approved') 
        
        // FILTRO OBLIGATORIO: Al menos un servicio activo global
        ->whereHas('addresses.services', function ($query) {
            $query->where('services.active', true);
        })
        
        // Filtro por especialidad
        ->when($request->specialty, function ($query) use ($request) {
            $query->whereHas('specialties', function ($q) use ($request) {
                $q->where('slug', $request->specialty);
            });
        })
        // Filtro por ciudad
        ->when($request->city, function ($query) use ($request) {
            $query->whereHas('addresses.city', function ($q) use ($request) {
                $q->where('slug', $request->city);
            });
        })
        ->paginate(10);

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
    
    public function searchSymptomView(Request $request)
    {
        // Solo cargamos las especialidades por si deseas usarlas en algún menú, si no, puedes dejarlo vacío
        return view('search.search-symptom');
    }

    /**
     * Procesa el síntoma de forma asíncrona mediante OpenAI y almacena en caché e IndexedSymptom.
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
            // 2. Sistema de Caché e IA
            $triage = Cache::remember($sintomaClave, 1440, function () use ($queryStr, $stringEspecialidades) {
                
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o-mini', // Económico, rápido y soporta Structured Outputs
                    'messages' => [
                        ['role' => 'system', 'content' => 'Eres un asistente médico experto de un SAAS de salud.'],
                        ['role' => 'user', 'content' => "Analiza este síntoma: '{$queryStr}'. Selecciona una especialidad de esta lista: {$stringEspecialidades}. Si ninguna encaja, usa 'General'."]
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'triage_medico_saas',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'especialidad_correcta' => ['type' => 'string', 'description' => 'Nombre exacto de la lista.'],
                                    'especialidad_slug' => ['type' => 'string', 'description' => 'El slug en minúsculas, usando guiones en lugar de espacios (ej: medicina-general, cardiologia).'],
                                    'urgencia' => ['type' => 'string', 'enum' => ['Alta', 'Media', 'Baja']],
                                    'consejo' => ['type' => 'string', 'description' => 'Breve orientación al paciente.'],
                                    'seo_title' => ['type' => 'string', 'description' => 'Título SEO optimizado para la landing de este síntoma.'],
                                    'seo_description' => ['type' => 'string', 'description' => 'Meta descripción SEO para la landing de este síntoma.']
                                ],
                                'required' => ['especialidad_correcta', 'especialidad_slug', 'urgencia', 'consejo', 'seo_title', 'seo_description'],
                                'additionalProperties' => false
                            ]
                        ]
                    ]
                ]);

                return json_decode($response->choices[0]->message->content, true);
            });

            // 3. LÓGICA DE ALMACENAMIENTO PARA SEO (Evita duplicados)
            $slug = Str::slug($queryStr);
            $specialtyId = $specialtiesMap[$triage['especialidad_correcta']] ?? null;

            $indexedSymptom = IndexedSymptom::firstOrNew(['slug' => $slug]);
            
            if (!$indexedSymptom->exists) {
                $indexedSymptom->fill([
                    'search_query' => $queryStr,
                    'specialty_id' => $specialtyId,
                    'seo_title' => $triage['seo_title'],
                    'seo_description' => $triage['seo_description'],
                    'urgency_level' => $triage['urgencia'],
                    'ai_advice' => $triage['consejo']
                ]);
                $indexedSymptom->save();
            } else {
                $indexedSymptom->increment('search_count'); // Incrementa popularidad si ya existía
            }

            // 4. Buscar doctores según la especialidad sugerida por OpenAI
            $doctors = Doctor::with(['user', 'specialties', 'addresses.city', 'addresses.services'])
                ->where('active', true)
                ->where('validation_status', 'approved')
                ->whereHas('specialties', function ($q) use ($specialtyId) {
                    $q->where('specialties.id', $specialtyId);
                })
                ->take(10) // Limitamos para la respuesta AJAX rápida
                ->get();

            return response()->json([
                'exito' => true,
                'triage' => $triage,
                'medicos' => $doctors
            ]);

        } catch (\Exception $e) {
            Log::error('Error en búsqueda por síntoma: ' . $e->getMessage());
            return response()->json([
                'exito' => false,
                'mensaje' => 'No pudimos procesar tu solicitud en este momento.',
                'medicos' => []
            ], 500);
        }
    }    

    /**
     * Renderiza la Landing Page dinámica para SEO optimizada para Google y pacientes.
     */
    public function showSymptomLanding($slug)
    {
        // 1. Buscar el síntoma usando la relación indexada por ID (Mucho más rápido que un LIKE)
        $symptom = IndexedSymptom::with('specialty')->where('slug', $slug)->firstOrFail();

        // 2. Construir la consulta de doctores optimizada cargando solo lo necesario
        $doctors = Doctor::with([
                'user:id,name', 
                'specialties:id,name,slug', 
                'addresses.city:id,name',
                'addresses.services'
            ])
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->whereHas('specialties', function ($q) use ($symptom) {
                $q->where('specialties.id', $symptom->specialty_id);
            })
            ->paginate(10)
            ->withQueryString(); // Mantiene parámetros si los hay

        // 3. Control de Crawl Budget y paginación profunda para Google Bot
        $page = request()->get('page', 1);
        $pageSuffix = $page > 1 ? " - Página {$page}" : "";
        $metaRobots = $page > 1 ? 'noindex, follow' : 'index, follow';

        // 4. Preparar Meta Tags (Usando los textos optimizados que OpenAI guardó en la DB)
        $seoTitle = $symptom->seo_title . $pageSuffix;
        $seoDescription = $symptom->seo_description;
        $canonicalUrl = route('symptoms.landing', ['slug' => $slug]);

        // 5. Retornamos la vista final enviando absolutamente todo empaquetado
        return view('search.symptom-landing', compact(
            'symptom', 
            'doctors', 
            'seoTitle', 
            'seoDescription', 
            'metaRobots', 
            'canonicalUrl'
        ));
    }

    /**
     * Muestra el directorio médico oficial filtrado por especialidad y ciudad,
     * ordenado estrictamente por la jerarquía de planes reales de doctor_settings.
     */
    public function medicalDirectory(Request $request)
    {
        // 1. Catálogos para los selectores del buscador superior
        $specialties = Specialty::orderBy('name', 'asc')->get();
        
        $cities = City::whereHas('addresses.doctor', function($q) {
            $q->where('active', true)->where('validation_status', 'approved');
        })->orderBy('name', 'asc')->get();

        // 2. Consulta base uniendo con doctor_settings y plans para ordenar jerárquicamente
        $query = Doctor::with([
                'user:id,name,profile_photo_path', // SOLUCIÓN AL ERROR: Solo columnas reales
                'specialties:id,name,slug', 
                'addresses.city:id,name',
                'settings.plan' // Relación hasOneThrough/belongsTo que tengas configurada
            ])
            ->select('doctors.*') // Protege el ID del doctor de colisiones en el JOIN
            ->join('doctor_settings', 'doctors.id', '=', 'doctor_settings.doctor_id')
            ->join('plans', 'doctor_settings.plan_id', '=', 'plans.id')
            
            // FILTROS OBLIGATORIOS DE SEGURIDAD
            ->where('doctors.active', true)
            ->where('doctors.validation_status', 'approved');

        // 3. FILTROS DINÁMICOS POR ENTRADA DE USUARIO
        if ($request->filled('specialty')) {
            $query->whereHas('specialties', function ($q) use ($request) {
                $q->where('specialties.slug', $request->specialty);
            });
        }

        if ($request->filled('city')) {
            $query->filterByCity($request->city);
        }

        // 4. ORDENAMIENTO POR PLAN (Usa 'slug' o 'name' de tu tabla plans según como los guardes)
        $doctors = $query->orderByRaw("FIELD(plans.slug, 'gold', 'premium', 'free') ASC")
            ->orderBy('doctors.rating', 'desc') // Criterio secundario: mejor calificados
            ->paginate(12);

        // Mantiene los parámetros activos al cambiar de página
        $doctors->appends($request->all());

        $seoTitle = "Directorio Médico de Especialistas Verificados | OpenDoctor";
        $seoDescription = "Encuentra y agenda cita con los mejores médicos especialistas de nuestra red de salud de OpenDoctor.";
        
        return view('search.medical-directory', compact('doctors', 'specialties', 'cities', 'seoTitle', 'seoDescription'));
    }
}
