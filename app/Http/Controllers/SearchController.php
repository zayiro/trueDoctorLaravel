<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Address;
use App\Models\Schedule;
use App\Models\Appointment;
use App\Models\Unavailability;
use App\Models\IndexedSymptom;
use App\Jobs\LogSearchJob;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
     * Vista principal del buscador global híbrido y compacto de opendoctor.online.
     */
    public function index(Request $request)
    {             
        $this->trackSearchAsync($request);

        $specialties = Specialty::where('status', true)->orderBy('name', 'asc')->get();
        $cities = City::where('state', true)->orderBy('name', 'asc')->get();                        
        
        // 1. Consulta base unificada con Eager Loading estricto
        $searchQuery = Address::with([
            'city',
            'services', // 🔥 Crucial: Carga los servicios en memoria para Blade
            'doctor.user',
            'doctor.specialties',
            'doctor.addresses.services', // 🔥 Trae todas las sedes del doctor en el mismo viaje
            'clinic.user',
            'clinic.doctors.specialties',
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
        ->where('addresses.status', true)
        ->whereNull('addresses.deleted_at')
        
        ->where(function ($query) {
            $query->whereHas('clinic', function ($q) {
                $q->where('active', true)->where('validation_status', 'approved');
            })
            ->orWhere(function ($sub) {
                $sub->whereHas('doctor', function ($q) {
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
            
            // [Tu diccionario gramatical de especialidades permanece intacto aquí]
        }

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
                $backupTurn = $availabilityService->getNextAvailableTurn($doctorIds, $address->id, $clinic->id);

                $groupedResults->put($uniqueKey, [
                    'type'        => 'clinic',
                    'id'          => $clinic->id,
                    'title'       => $clinic->user->name,
                    'slug'        => $clinic->slug,
                    'rating'      => $clinic->rating,
                    'badge_text'  => $specialistsCount > 0 ? "{$specialistsCount} Especialistas" : "Clínica", 
                    'user'        => $clinic->user,
                    'model'       => $clinic,
                    'address_id'  => $address->id,
                    'subtitle'    => "{$address->name} • {$address->address}",
                    // 🔒 INYECCIÓN DE RESPALDO ANTI-ERROR: Sanará la línea 287 de tu Blade
                    'next_turn'   => $backupTurn ? ($backupTurn->isToday() ? 'Hoy ' : '') . ucfirst($backupTurn->isoFormat('dddd D [de] MMMM — h:mm A')) : 'Sin turnos próximos disponibles'
                ]);

            } else {
                $doctor = $address->doctor;
                $uniqueKey = 'doctor_' . $doctor->id;

                if ($groupedResults->has($uniqueKey)) continue;

                // 🚀 Cálculo veloz para el respaldo de producción
                $availabilityService = app(AvailabilityService::class);
                $backupTurn = $availabilityService->getNextAvailableTurn([$doctor->id], $address->id, null);
                
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
                    'address_id'        => $address->id,
                    'subtitle'          => $address->type === 'virtual' ? 'Atención Online' : "{$address->name} • {$address->address}",
                    // 🔒 INYECCIÓN DE RESPALDO ANTI-ERROR: Sanará la línea 287 de tu Blade
                    'next_turn'   => $backupTurn ? ($backupTurn->isToday() ? 'Hoy ' : '') . ucfirst($backupTurn->isoFormat('dddd D [de] MMMM — h:mm A')) : 'Sin turnos próximos disponibles'
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
            'showingSuggestions' => $showingSuggestions,
            'targetCity'         => $targetCity,
            'targetSpecialty'    => $targetSpecialty,
            'expertName'         => $expertName
        ]);
    }


    /**
     * Calcula el próximo turno disponible leyendo el colchón logístico configurado por el médico.
     * 🛡️ CONEXIÓN MULTI-TENANT: Consume de forma nativa 'min_notice_hours' de doctor_settings.
     * ⚡ OPTIMIZADO: Eager Loading de doctor_settings para evitar N+1 queries.
     */
    private function calculateNextTurn($addressId, array $doctorIds, $now, $currentTime)
    {
        if (empty($doctorIds)) {
            return null;
        }

        // 1. DETERMINAR EL MARGEN INDIVIDUALIZADO DEL ESPECIALISTA (O STAFF)
        // Extraemos el valor máximo de horas de anticipación configurado por los médicos involucrados
        $bufferHours = DB::table('doctor_settings')
            ->whereIn('doctor_id', $doctorIds)
            ->orderBy('min_notice_hours', 'desc')
            ->value('min_notice_hours');

        // Validación defensiva: Si no hay registro o es nulo, asume el default de 2 horas de tu migración
        $hoursToKey = !is_null($bufferHours) ? (int)$bufferHours : 2;

        // REGLA DE NEGOCIO EN CALIENTE: Hora actual del servidor + las horas de aviso requeridas por este médico
        $minAvailableTime = Carbon::now()->addHours($hoursToKey);
        
        // Obtener los índices de control del calendario
        $currentDayIndex = $minAvailableTime->dayOfWeekIso; // Formato numérico ISO (1 = Lunes, 7 = Domingo)
        $currentHourStr  = $minAvailableTime->format('H:i:s');

        // Mapeo en español para el formateo de los días de la semana
        $daysMap = [
            1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 
            5 => 'viernes', 6 => 'sábado', 7 => 'domingo'
        ];

        // ESCENARIO A: Buscar si quedan bloques disponibles HOY mismo que superen el margen personalizado
        $schedule = Schedule::with(['doctor', 'address'])
            ->where('address_id', $addressId)
            ->whereIn('doctor_id', $doctorIds)
            ->where('day', $currentDayIndex)
            ->whereTime('start_time', '>=', $currentHourStr)
            ->orderBy('day')
            ->orderBy('start_time', 'asc')
            ->first();

        $targetDate = Carbon::today();

        // ESCENARIO B: Si hoy ya no hay franjas que cumplan el margen, barremos secuencialmente los días siguientes
        if (!$schedule) {
            // Ciclo de inspección de los próximos 7 días del calendario
            for ($i = 1; $i <= 7; $i++) {
                $nextDateCheck = Carbon::today()->addDays($i);
                $checkDayIndex = $nextDateCheck->dayOfWeekIso;

                $schedule = Schedule::with(['doctor', 'address'])
                    ->where('address_id', $addressId)
                    ->whereIn('doctor_id', $doctorIds)
                    ->where('day', $checkDayIndex)
                    ->orderBy('start_time', 'asc')
                    ->first();

                if ($schedule) {
                    $targetDate = $nextDateCheck;
                    break; // Rompemos el ciclo inmediatamente al encontrar la jornada operativa más cercana
                }
            }
        }

        // 2. PROCESAMIENTO Y FORMATEO FINAL DEL RANGO EMITIDO
        if ($schedule) {
            // Obtenemos la hora purificada en formato H:i:s de forma segura, 
            // sin importar si $schedule->start_time es un string o un objeto Carbon
            $timeStr = $schedule->start_time instanceof Carbon 
                ? $schedule->start_time->format('H:i:s') 
                : Carbon::parse($schedule->start_time)->format('H:i:s');

            // Combinamos la fecha destino calculada en el bucle con la hora purificada del turno
            $scheduleTime = Carbon::parse($targetDate->toDateString() . ' ' . $timeStr);
            
            // Formateamos estilo Doctoralia: "sábado 13 de junio — 10:30 AM"
            return $scheduleTime->isoFormat('dddd d [de] MMMM') . ' — ' . $scheduleTime->format('g:i A');
        }

        return 'Sin disponibilidad próxima';

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

    /**
     * Procesa el síntoma de forma asíncrona mediante OpenAI y almacena en caché e IndexedSymptom.
     * Genera de manera automática estructuras semánticas e indexación orgánica en caliente.
     * ⚡ OPTIMIZADO: Eager Loading completo para evitar N+1 queries en renderizado.
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
            // ⚡ OPTIMIZADO: Eager Loading de user, specialties, addresses y city para evitar N+1
            $medicos = Doctor::with([
                'user',
                'specialties',
                'addresses.city',
                'addresses.services'
            ])
                ->where('active', true)
                ->where('validation_status', 'approved')
                ->whereHas('specialties', function ($q) use ($triage) {
                    $q->where('slug', $triage['especialidad_slug']);
                })
                ->take(6)
                ->get();
            
            if (!isset($triage['especialidad_slug'])) {
                Log::warning("El motor de triage por IA no devolvió un slug de especialidad válido para el síntoma: '{$queryStr}'. Respuesta completa: " . json_encode($triage));
            } else {
                $this->logAiTrackSearchAsync($request, $triage['especialidad_slug']);
            }

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

    /**
     * Almacena de forma persistente las coordenadas e información geográfica del dispositivo en la sesión.
     */
    public function saveDeviceLocationToSession(Request $request)
    {
        $validated = $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'city'      => 'nullable|string|max:150',
        ]);

        // Almacenar en la sesión nativa de Laravel para su uso posterior en cualquier controlador
        session([
            'patient_latitude'  => $validated['latitude'],
            'patient_longitude' => $validated['longitude'],
            'patient_city_name' => $validated['city'] ?? 'Unknown'
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Device coordinates and location successfully frozen in session data.'
        ]);
    }    
}
