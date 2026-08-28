<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Address;
use App\Models\IndexedSymptom;
use App\Jobs\LogSearchJob;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\AvailabilityService;

class SearchController extends Controller
{
    protected $availabilityService;

    /**
     * Inyección nativa del servicio de disponibilidad.
     */
    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

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

        $specialties = Specialty::where('status', true)->orderBy('name', 'asc')->get();

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
            'specialties', 
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
     * Método privado asíncrono para despachar el log de búsqueda.
     * guarda en la tabla 'search_logs' cada intento de búsqueda con especialidad, ciudad, país e IP del usuario.
     * Utiliza un Job para no afectar la experiencia del usuario y asegurar que el proceso de logging no genere latencia en la respuesta del buscador.
     */
    private function trackSearchAsync(Request $request): void
    {
        $specialty = $request->input('specialty');
        $city = $request->input('city'); // Puede ser string o null

        // Registramos únicamente si la especialidad contiene texto válido
        if (!empty($specialty)) {
            //afterResponse() asegura cero demoras visuales para el usuario
            LogSearchJob::dispatch($specialty, $city, $request->ip())->afterResponse();
        }
    }

    /**
     * MÉTODO PRIVADO: Registra la búsqueda usando los datos analíticos.
     */
    private function logAiTrackSearchAsync(Request $request, $specialty)
    {        
        $city = $request->input('city'); // Puede ser string o null
        
        // Registramos únicamente si la especialidad contiene texto válido
        if (!empty($specialty)) {
            //afterResponse() asegura cero demoras visuales para el usuario
            LogSearchJob::dispatch($specialty, $city, $request->ip())->afterResponse();
        }        
    }
        
    /**
     * Vista principal del buscador global híbrido y compacto de opendoctor.online.
     */
    public function index(Request $request)
    {             
        $this->trackSearchAsync($request);

        $specialties = Specialty::where('status', true)->orderBy('name', 'asc')->get();
        $cities = City::where('state', true)->orderBy('name', 'asc')->get();
        $symptoms = IndexedSymptom::inRandomOrder()->limit(10)->pluck('search_query')->toArray();
        
        // 1. Consulta base unificada con Eager Loading estricto
        $searchQuery = Address::with([
            'city',
            'services', // 🔥 Crucial: Carga los servicios en memoria para Blade
            'doctor.user',
            'doctor.specialties',
            // 🔥 SOLUCIÓN: Filtramos las otras sedes del doctor en el Eager Loading
            'doctor.addresses' => function ($query) {
                $query->where('status', true)->whereNull('deleted_at');
            },
            'doctor.addresses.services', // 🔥 Trae todas las sedes del doctor en el mismo viaje
            'clinic.user',
            'clinic.doctors.specialties',
            // 🔥 SOLUCIÓN: Hacemos lo mismo para las sedes de las clínicas por si acaso
            'clinic.addresses' => function ($query) {
                $query->where('status', true)->whereNull('deleted_at');
            },
            'clinic.addresses.services' // 🔥 Trae todas las sedes de la clínica en el mismo viaje
        ])
        ->addSelect([
            'owner_plan_price' => function ($subQuery) {
                $subQuery->select('plans.price')
                    ->from('plans')
                    ->leftJoin('clinic_settings', 'plans.id', '=', 'clinic_settings.plan_id')
                    ->leftJoin('doctor_settings', 'plans.id', '=', 'doctor_settings.plan_id')
                    ->where(function ($query) {
                        $query->whereColumn('clinic_settings.clinic_id', 'addresses.clinic_id')
                            ->orWhereColumn('doctor_settings.doctor_id', 'addresses.doctor_id');
                    })
                    ->limit(1);
            },
            'owner_rating' => function ($subQuery) {
                $subQuery->selectRaw('COALESCE(clinics.rating, doctors.rating)')
                    ->from('addresses as addr')
                    ->leftJoin('clinics', 'clinics.id', '=', 'addr.clinic_id')
                    ->leftJoin('doctors', 'doctors.id', '=', 'addr.doctor_id')
                    ->whereColumn('addr.id', 'addresses.id')
                    ->limit(1);
            }
        ])
        // 1. Filtros globales estrictos para la dirección (SIEMPRE SE DEBEN CUMPLIR)
        ->where('addresses.status', true)
        ->whereNull('addresses.deleted_at')
        
        // 2. Filtro agrupado encapsulado en paréntesis para los propietarios
        ->where(function ($query) {
            $query->where(function ($q1) {
                $q1->whereHas('clinic', function ($q) {
                    $q->where('active', true)->where('validation_status', 'approved');
                });
            })
            ->orWhere(function ($q2) {
                $q2->whereHas('doctor', function ($q) {
                    $q->where('active', true)->where('validation_status', 'approved');
                });
            });
        });
        
        // Filtro condicional por Especialidad Médica (Slug)
        $searchQuery->when($request->specialty, function ($query) use ($request) {
            $query->where(function ($sub) use ($request) {
                $sub->whereHas('doctor.specialties', function ($q) use ($request) {
                    $q->where('specialties.slug', $request->specialty);
                })
                ->orWhereHas('clinic.doctors.specialties', function ($q) use ($request) {
                    $q->where('specialties.slug', $request->specialty);
                });
            });
        });

        $backupSearchQuery = clone $searchQuery;

        $searchQuery->when($request->city, function ($query) use ($request) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('slug', $request->city);
            });
        });

        $addresses = $searchQuery->orderBy('owner_plan_price', 'desc') 
            ->orderBy('owner_rating', 'desc')                          
            ->get();
        
        $showingSuggestions = false;
        
        $targetCity = $request->city ? City::where('slug', $request->city)->first() : null;
        $targetSpecialty = $request->specialty ? Specialty::where('slug', $request->specialty)->first() : null;
        $expertName = 'especialistas';

        if ($addresses->isEmpty() && $targetCity) {
            $showingSuggestions = true;
            $addresses = $backupSearchQuery->where(function ($query) use ($targetCity) {
                $query->where('addresses.type', 'virtual')
                      ->orWhereHas('city', function ($q) use ($targetCity) {
                          $q->where('id', '!=', $targetCity->id);
                      });
            })
            ->orderBy('owner_plan_price', 'desc') 
            ->orderBy('owner_rating', 'desc')
            ->limit(12)
            ->get();
        }

        $hasPhysical = $addresses->contains('type', 'physical');
        $hasVirtual = $addresses->contains('type', 'virtual');
        
        // 2. PROCESAMIENTO HÍBRIDO CON RESPALDO MAESTRO DE DISPONIBILIDAD
        $groupedResults = collect();
        
        foreach ($addresses as $address) {
            $isClinic = !is_null($address->clinic_id);

            if ($isClinic) {
                $clinic = $address->clinic;
                $uniqueKey = 'clinic_' . $clinic->id;

                if ($groupedResults->has($uniqueKey)) continue;

                $doctorsQuery = $clinic->doctors();
                if ($request->filled('specialty')) {
                    $doctorsQuery->whereHas('specialties', function ($q) use ($request) {
                        $q->where('specialties.slug', $request->specialty);
                    });
                }
                $doctorIds = $doctorsQuery->pluck('doctors.id')->toArray();
                $specialistsCount = count($doctorIds);

                // 🚀 Cálculo veloz para el respaldo de producción                
                $availabilityService = app(AvailabilityService::class);
                $backupTurn = $availabilityService->getNextAvailableTurnAnyAddress(
                    $doctorIds,
                    $clinic->id  // ✅ Busca en TODAS las sedes
                );

                $groupedResults->put($uniqueKey, [
                    'type'        => 'clinic',
                    'id'          => $clinic->id,
                    'title'       => $clinic->user->name,
                    'slug'        => $clinic->slug,
                    'rating'      => $clinic->rating,
                    'badge_text'  => $specialistsCount > 0 ? "{$specialistsCount} Especialistas" : "Clínica", 
                    'user'        => $clinic->user,
                    'model'       => $clinic,
                    'address_id'  => $request->city ? $address->id : null,
                    'subtitle'    => "{$address->name} • {$address->address}",                    
                    'next_turn'   => $backupTurn ? ($backupTurn->isToday() ? 'Hoy ' : '') . ucfirst($backupTurn->isoFormat('dddd D [de] MMMM — h:mm A')) : 'Sin turnos próximos disponibles'
                ]);

            } else {
                $doctor = $address->doctor;
                $uniqueKey = 'doctor_' . $doctor->id;

                if ($groupedResults->has($uniqueKey)) continue;

                // 🚀 Cálculo veloz para el respaldo de producción               
                $availabilityService = app(AvailabilityService::class);
                $backupTurn = $availabilityService->getNextAvailableTurnAnyAddress(
                    [$doctor->id],  // ✅ Busca en TODAS las sedes del doctor
                    null
                );

                $langNames = ['co' => 'Colombia', 'es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués', 'fr' => 'Francés', 'de' => 'Alemán'];
                $rawLang = $doctor->languages;
                $decodedLang = is_array($rawLang) ? $rawLang : (json_decode($rawLang, true) ?? []);

                $langFlags = [
                    'co' => 'co',
                    'es' => 'es',
                    'en' => 'us',
                    'pt' => 'br',
                    'fr' => 'fr',
                    'de' => 'de',
                    'it' => 'it',
                    'zh' => 'cn',
                    'ar' => 'sa',
                ];
                
                $languages = array_map(fn($code) => [
                    'code' => $code,
                    'name' => $langNames[$code] ?? strtoupper($code),
                    'flag' => $langFlags[$code] ?? 'un', // 'un' = bandera ONU como fallback
                ], $decodedLang);
                                              
                $groupedResults->put($uniqueKey, [
                    'type'              => 'doctor',
                    'id'                => $doctor->id,
                    'title'             => ($doctor->gender === 'female' ? 'Dra. ' : 'Dr. ') . ucfirst($doctor->user->name),
                    'slug'              => $doctor->slug,
                    'rating'            => $doctor->rating,
                    'badge_text'        => $targetSpecialty ? $targetSpecialty->name : ($doctor->specialties->first()->name ?? 'Consultorio Privado'), 
                    'user'              => $doctor->user,
                    'model'             => $doctor,
                    'specialties_count' => $doctor->specialties->count(),
                    'languages'         => $languages,
                    'countryName'       => $doctor->country_name,
                    'country_code'      => $doctor->country_code,
                    'address_id'        => $request->city ? $address->id : null,
                    'subtitle'          => $address->type === 'virtual' ? 'Atención Online' : "{$address->name} • {$address->address}",                  
                    'next_turn'         => $backupTurn ? ($backupTurn->isToday() ? 'Hoy ' : '') . ucfirst($backupTurn->isoFormat('dddd D [de] MMMM — h:mm A')) : 'Sin turnos próximos disponibles'
                ]);
            }
        }
    
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        
        $resultsPage = new LengthAwarePaginator(
            $groupedResults->values()->forPage($page, $perPage)->values(),
            $groupedResults->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        $resultsPage->appends($request->all());
        
        return view('search.index', [
            'results'            => $resultsPage,
            'specialties'        => $specialties,
            'cities'             => $cities,
            'symptoms'           => $symptoms,
            'showingSuggestions' => $showingSuggestions,
            'targetCity'         => $targetCity,
            'targetSpecialty'    => $targetSpecialty,
            'expertName'         => $expertName,
            'hasPhysical'        => $hasPhysical,
            'hasVirtual'         => $hasVirtual,
        ]);
    }

    /**
     * Despacha los resultados de coincidencia exacta de criterios.
     * ⚡ OPTIMIZADO: Eager Loading completo para evitar N+1 queries en renderizado.
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
            'addresses' => function ($query) use ($request) {
                if ($request->filled('city')) {
                    $query->whereHas('city', function ($q) use ($request) {
                        $q->where('slug', $request->city);
                    });
                }
            },
            'addresses.city',
            'addresses.services',
            'clinics.addresses.city',
            'clinics.addresses.services'
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

    public function searchBySymptom(Request $request): JsonResponse
    {
        $queryStr = trim($request->input('symptom', ''));

        if (empty($queryStr) || strlen($queryStr) < 3) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El síntoma debe tener al menos 3 caracteres.',
                'doctors' => []
            ], 400);
        }

        $specialtiesMap       = Specialty::pluck('id', 'name')->toArray();
        $stringEspecialidades = implode(', ', array_keys($specialtiesMap));
        $sintomaClave         = 'triage_' . Str::slug($queryStr);

        try {
            // ── 1. Caché (solo se guarda si la IA respondió correctamente) ──
            $triage = Cache::get($sintomaClave);

            if (!$triage) {
                $triage = $this->callAiTriage($queryStr, $stringEspecialidades);
                $this->validateTriageResponse($triage, $queryStr);
                Cache::put($sintomaClave, $triage, now()->addHours(24));
            }

            // ── 2. Indexación SEO
            $slugSintoma = Str::slug($queryStr);
            $specialtyId = $specialtiesMap[$triage['especialidad_correcta']] ?? null;

            IndexedSymptom::firstOrCreate(
                ['slug' => $slugSintoma],
                [
                    'search_query'    => $queryStr,
                    'specialty_id'    => $specialtyId,
                    'seo_title'       => $triage['seo_title'],
                    'seo_description' => $triage['seo_description'],
                    'urgency_level'   => $triage['urgencia'],
                    'ai_advice'       => $triage['consejo'],
                ]
            );
            
            // ── 3. Médicos y clínicas disponibles

            // Doctores
            $medicos = Doctor::with([
                'user',
                'specialties',
                'addresses.city',
                'addresses.services',
            ])
                ->where('active', true)
                ->where('validation_status', 'approved')
                ->whereHas('specialties', fn($q) => $q->where('slug', $triage['especialidad_slug']))
                ->take(4)
                ->get()
                ->map(fn($d) => array_merge($d->toArray(), ['result_type' => 'doctor']));

            // Clínicas
            $clinicas = Clinic::with([
                'user',
                'doctors.specialties',
                'addresses.city',
                'addresses.services',
            ])
                ->where('active', true)
                ->where('validation_status', 'approved')
                ->whereHas('doctors.specialties', fn($q) => $q->where('slug', $triage['especialidad_slug']))
                ->take(2)
                ->get()
                ->map(fn($c) => array_merge($c->toArray(), ['result_type' => 'clinic']));

            $results = $medicos->concat($clinicas)->values();

            $this->logAiTrackSearchAsync($request, $triage['especialidad_slug']);

            // ── 4. Respuesta con keys en inglés para el frontend
            return response()->json([
                'success'       => true,
                'triage'        => [
                    'advice'          => $triage['consejo'],
                    'urgency'         => $triage['urgencia'],
                    'specialty_name'  => $triage['especialidad_correcta'],
                    'specialty_slug'  => $triage['especialidad_slug'],
                    'seo_title'       => $triage['seo_title'],
                    'seo_description' => $triage['seo_description'],
                ],
                'medicos_count' => $medicos->count(),
                'results'       => $results,
            ]);

        } catch (\Exception $e) {
            Log::error("Fallo crítico en el motor de triage por IA: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'mensaje' => 'No pudimos procesar tu diagnóstico por síntomas en este momento. Por favor, selecciona una especialidad manualmente.',
                'doctors' => []
            ], 500);
        }
    }

    // Métodos privados de soporte

    private function callAiTriage(string $queryStr, string $especialidades): array
    {
        try {
            return $this->callOpenAi($queryStr, $especialidades);
        } catch (\Exception $e) {
            Log::warning("OpenAI falló, activando fallback a DeepSeek: " . $e->getMessage());
            return $this->callDeepSeek($queryStr, $especialidades);
        }
    }

    private function callOpenAi(string $queryStr, string $especialidades): array
    {
        $response = OpenAI::chat()->create([
            'model'    => config('services.openai.vision_model'),
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente médico experto de un SAAS de salud llamado opendoctor.online.'],
                ['role' => 'user',   'content' => "Analiza este síntoma: '{$queryStr}'. Selecciona una especialidad de esta lista: {$especialidades}. Si ninguna encaja de forma directa, usa 'medicina-general'."],
            ],
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'triage_medico_saas',
                    'strict' => true,
                    'schema' => $this->triageJsonSchema(),
                ],
            ],
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }

    private function callDeepSeek(string $queryStr, string $especialidades): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.deepseek.key'),
            'Content-Type'  => 'application/json',
        ])->post(config('services.deepseek.url'), [
            'model'    => config('services.deepseek.vision_model'),
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente médico experto. Responde SOLO en JSON válido, sin bloques de código ni explicaciones.'],
                ['role' => 'user',   'content' => "Analiza este síntoma: '{$queryStr}'. Devuelve un JSON con los campos: especialidad_correcta, especialidad_slug, urgencia (Alta|Media|Baja), consejo, seo_title, seo_description. Usa esta lista de especialidades: {$especialidades}."],
            ],
            'temperature' => 0.3,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("DeepSeek también falló: " . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        $content = preg_replace('/^```json\s*/i', '', trim($content));
        $content = preg_replace('/```$/', '', trim($content));

        return json_decode($content, true);
    }

    private function validateTriageResponse(?array $triage, string $queryStr): void
    {
        $required = ['especialidad_correcta', 'especialidad_slug', 'urgencia', 'consejo', 'seo_title', 'seo_description'];

        if (!is_array($triage)) {
            throw new \RuntimeException("La IA devolvió una respuesta no parseable para el síntoma: '{$queryStr}'");
        }

        foreach ($required as $field) {
            if (empty($triage[$field])) {
                throw new \RuntimeException("Campo requerido '{$field}' ausente en respuesta de IA para: '{$queryStr}'");
            }
        }
    }

    private function triageJsonSchema(): array
    {
        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['especialidad_correcta', 'especialidad_slug', 'urgencia', 'consejo', 'seo_title', 'seo_description'],
            'properties'           => [
                'especialidad_correcta' => ['type' => 'string', 'description' => 'Nombre exacto de la especialidad tomada de la lista provista.'],
                'especialidad_slug'     => ['type' => 'string', 'description' => 'Slug de la especialidad en minúsculas separado por guiones.'],
                'urgencia'              => ['type' => 'string', 'enum' => ['Alta', 'Media', 'Baja']],
                'consejo'               => ['type' => 'string', 'description' => 'Breve orientación preliminar al paciente.'],
                'seo_title'             => ['type' => 'string', 'description' => 'Título SEO optimizado para la landing de este síntoma.'],
                'seo_description'       => ['type' => 'string', 'description' => 'Meta-descripción SEO indexable.'],
            ],
        ];
    }
}
