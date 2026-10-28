<?php

declare(strict_types=1);

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
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Genera el archivo dinámico sitemap.xml para la indexación de Google y Bing.
     */
    public function generateSitemap()
    {
        $doctors = Doctor::select(['id', 'slug', 'updated_at'])
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        $specialties = Specialty::where('status', true)->orderBy('name', 'asc')->get();

        $clinics = Clinic::select(['id', 'slug', 'updated_at'])
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        $indexedSymptoms = IndexedSymptom::select(['id', 'slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $sitemapContent = view('seo.sitemap', compact(
            'doctors',
            'specialties',
            'clinics',
            'indexedSymptoms'
        ))->render();

        return response($sitemapContent, 200, [
            'Content-Type'  => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }

    /**
     * Método privado asíncrono para despachar el log de búsqueda.
     * Guarda en la tabla 'search_logs' cada intento de búsqueda con especialidad, ciudad, país e IP.
     */
    private function trackSearchAsync(Request $request): void
    {
        $specialty = $request->input('specialty');
        $city = $request->input('city');
        
        if (!empty($specialty)) {
            LogSearchJob::dispatch($specialty, $city, $request->ip())->afterResponse();
        }
    }

    /**
     * Método privado para registrar búsqueda analítica de IA.
     */
    private function logAiTrackSearchAsync(Request $request, $specialty): void
    {
        $city = $request->input('city');
        
        if (!empty($specialty)) {
            LogSearchJob::dispatch($specialty, $city, $request->ip())->afterResponse();
        }
    }

    /**
     * Vista principal del buscador global híbrido.
     * 
     * 🔒 REFACTORIZACIÓN CRÍTICA:
     * 1. Agrupa por doctor/clínica ANTES de calcular disponibilidad
     * 2. Ordena $doctorIds para garantizar claves de caché deterministas
     * 3. Evita cálculos redundantes por cada sede del mismo profesional
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
            'services',
            'doctor.user',
            'doctor.specialties',
            'doctor.addresses' => function ($query) {
                $query->where('status', true)->whereNull('deleted_at');
            },
            'doctor.addresses.services',
            'clinic.user',
            'clinic.doctors.specialties',
            'clinic.addresses' => function ($query) {
                $query->where('status', true)->whereNull('deleted_at');
            },
            'clinic.addresses.services'
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
                    })->limit(1);
            },
            'owner_rating' => function ($subQuery) {
                $subQuery->selectRaw('COALESCE(clinics.rating, doctors.rating)')
                    ->from('addresses as addr')
                    ->leftJoin('clinics', 'clinics.id', '=', 'addr.clinic_id')
                    ->leftJoin('doctors', 'doctors.id', '=', 'addr.doctor_id')
                    ->whereColumn('addr.id', 'addresses.id')->limit(1);
            }
        ])
        ->where('addresses.status', true)
        ->whereNull('addresses.deleted_at')
        ->where(function ($query) {
            $query->where(function ($q1) {
                $q1->whereHas('clinic', function ($q) {
                    $q->where('active', true)->where('validation_status', 'approved');
                });
            })->orWhere(function ($q2) {
                $q2->whereHas('doctor', function ($q) {
                    $q->where('active', true)->where('validation_status', 'approved');
                });
            });
        });

        // Filtro por Especialidad
        $searchQuery->when($request->specialty, function ($query) use ($request) {
            $query->where(function ($sub) use ($request) {
                $sub->whereHas('doctor.specialties', function ($q) use ($request) {
                    $q->where('specialties.slug', $request->specialty);
                })->orWhereHas('clinic.doctors.specialties', function ($q) use ($request) {
                    $q->where('specialties.slug', $request->specialty);
                });
            });
        });

        $backupSearchQuery = clone $searchQuery;

        // Filtro por Ciudad
        $searchQuery->when($request->city_id, function ($query) use ($request) {
            $query->where('addresses.city_id', $request->city_id);
        });

        $addresses = $searchQuery->orderBy('owner_plan_price', 'desc')
            ->orderBy('owner_rating', 'desc')
            ->get();

        $showingSuggestions = false;
        $targetCity = $request->filled('city_name') ? $request->input('city_name') . ' - ' . $request->input('city_countryName') : 'Bogotá, Colombia';
        $targetSpecialty = $request->specialty ? Specialty::where('slug', $request->specialty)->first() : null;

        // Lógica de Fallback (Ciudad -> País -> Virtual)
        if ($request->city_id && $addresses->isEmpty()) {
            $showingSuggestions = true;
            $addresses = $backupSearchQuery
                ->when($request->country_code, function ($query) use ($request) {
                    $query->where('addresses.country_code', $request->country_code);
                })
                ->orderBy('owner_plan_price', 'desc')
                ->orderBy('owner_rating', 'desc')
                ->limit(12)
                ->get();
        }

        // 2. PROCESAMIENTO HÍBRIDO OPTIMIZADO
        $groupedResults = collect();

        foreach ($addresses as $address) {
            $isClinic = !is_null($address->clinic_id);

            if ($isClinic) {
                $clinic = $address->clinic;
                $uniqueKey = 'clinic_' . $clinic->id;

                // ✅ CORRECCIÓN: Si ya procesamos esta clínica, saltamos (evita cálculos redundantes)
                if ($groupedResults->has($uniqueKey)) {
                    continue;
                }

                $doctorsQuery = $clinic->doctors();
                if ($request->filled('specialty')) {
                    $doctorsQuery->whereHas('specialties', function ($q) use ($request) {
                        $q->where('specialties.slug', $request->specialty);
                    });
                }

                // ✅ CORRECCIÓN CRÍTICA: ORDENAR los IDs es OBLIGATORIO para claves de caché deterministas
                $doctorIds = $doctorsQuery->pluck('doctors.id')->toArray();
                sort($doctorIds);

                $specialistsCount = count($doctorIds);

                // ✅ CORRECCIÓN: Calcular disponibilidad UNA SOLA VEZ por clínica
                $backupTurn = $this->availabilityService->getNextAvailableTurnAnyAddress(
                    $doctorIds,
                    $clinic->id
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
                    'address_id'  => $request->city_id ? $address->id : null,
                    'subtitle'    => "{$address->name} • {$address->address}",
                    'next_turn'   => $backupTurn 
                        ? ($backupTurn->isToday() ? 'Hoy ' : '') . ucfirst($backupTurn->isoFormat('dddd D [de] MMMM — h:mm A')) 
                        : 'Sin turnos próximos disponibles'
                ]);

            } else {
                $doctor = $address->doctor;
                $uniqueKey = 'doctor_' . $doctor->id;

                // ✅ CORRECCIÓN: Si ya procesamos este doctor, saltamos
                if ($groupedResults->has($uniqueKey)) {
                    continue;
                }

                // ✅ CORRECCIÓN: Ordenar (aunque sea 1 elemento, mantiene consistencia)
                $doctorIds = [$doctor->id];
                sort($doctorIds);

                $rawLang = $doctor->languages;
                $decodedLang = is_array($rawLang) ? $rawLang : (json_decode($rawLang, true) ?? []);
                $langNames = ['co' => 'Colombia', 'es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués', 'fr' => 'Francés', 'de' => 'Alemán'];
                $langFlags = ['co' => 'co', 'es' => 'es', 'en' => 'us', 'pt' => 'br', 'fr' => 'fr', 'de' => 'de', 'it' => 'it', 'zh' => 'cn', 'ar' => 'sa'];
                
                $languages = array_map(fn($code) => [
                    'code' => $code,
                    'name' => $langNames[$code] ?? strtoupper($code),
                    'flag' => $langFlags[$code] ?? 'un',
                ], $decodedLang);

                // ✅ CORRECCIÓN: Calcular disponibilidad UNA SOLA VEZ por doctor
                $backupTurn = $this->availabilityService->getNextAvailableTurnAnyAddress(
                    $doctorIds,
                    null
                );

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
                    'address_id'        => $request->city_id ? $address->id : null,
                    'subtitle'          => $address->type === 'virtual' ? 'Atención Online' : "{$address->name} • {$address->address}",
                    'next_turn'         => $backupTurn 
                        ? ($backupTurn->isToday() ? 'Hoy ' : '') . ucfirst($backupTurn->isoFormat('dddd D [de] MMMM — h:mm A')) 
                        : 'Sin turnos próximos disponibles'
                ]);
            }
        }

        // Paginación
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
            'expertName'         => 'especialistas',
            'hasPhysical'        => $addresses->contains('type', 'physical'),
            'hasVirtual'         => $addresses->contains('type', 'virtual'),
        ]);
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
     * Búsqueda por síntomas con triage de IA.
     */
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

        $specialtiesMap = Specialty::pluck('id', 'name')->toArray();
        $stringEspecialidades = implode(', ', array_keys($specialtiesMap));
        $sintomaClave = 'triage_' . Str::slug($queryStr);

        try {
            // 1. Caché (solo se guarda si la IA respondió correctamente)
            $triage = Cache::get($sintomaClave);
            if (!$triage) {
                $triage = $this->callAiTriage($queryStr, $stringEspecialidades);
                $this->validateTriageResponse($triage, $queryStr);
                Cache::put($sintomaClave, $triage, now()->addHours(24));
            }

            // 2. Indexación SEO
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

            // 3. Médicos y clínicas disponibles
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

            // 4. Respuesta con keys en inglés para el frontend
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

    // =========================================================================
    // MÉTODOS PRIVADOS DE SOPORTE PARA IA
    // =========================================================================

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

    /**
     * Guarda la ubicación del dispositivo en la sesión.
     */
    public function saveDeviceLocationToSession(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        if (!$latitude || !$longitude) {
            return response()->json(['error' => 'Ubicación inválida'], 400);
        }

        session([
            'device_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'saved_at' => now(),
            ]
        ]);

        Log::info('Ubicación del dispositivo guardada', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Ubicación guardada']);
    }
}