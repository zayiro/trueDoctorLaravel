<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Address;
use App\Models\Appointment;
use App\Models\Unavailability;
use App\Models\IndexedSymptom;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * Vista principal del buscador global híbrido y compacto de opendoctor.online.
     */
    public function index(Request $request)
    {         
        $specialties = Specialty::where('status', true)->orderBy('name', 'asc')->get();
        $cities = City::where('state', true)->orderBy('name', 'asc')->get();                        
        
        $now = Carbon::now();
        $currentDate = $now->toDateString();
        $currentTime = $now->toTimeString();

        // 1. Consulta base sobre el modelo maestro Address (Sedes unificadas)
        $searchQuery = Address::with([
            'city',
            'doctor' => function ($q) {
                $q->where('active', true)->where('validation_status', 'approved')->with(['user', 'specialties']);
            },
            'clinic' => function ($q) {
                $q->where('active', true)->where('validation_status', 'approved')->with(['user', 'doctors.specialties']);
            }
        ])
        // 🔒 SUBQUERIES LIMPIAS: Rescatamos el precio del plan del dueño para ordenar por Premium primero
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
            // Unificamos la calificación (Rating) del dueño de la sede para la ordenación estricta
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
        
        // 🚀 INYECCIÓN CRÍTICA SAAS: Asegurar entidad válida y excluir médicos institucionales sin plan propio
        ->where(function ($query) {
            // Condición A: La sede es de una clínica aprobada
            $query->whereHas('clinic', function ($q) {
                $q->where('active', true)->where('validation_status', 'approved');
            })
            // Condición B: La sede es de un médico, pero el médico DEBE tener un plan individual contratado
            ->orWhere(function ($sub) {
                $sub->whereHas('doctor', function ($q) {
                    $q->where('active', true)->where('validation_status', 'approved');
                })
                ->whereHas('doctor.settings', function ($q) {
                    $q->whereNotNull('plan_id'); // 🔒 Oculta de inmediato al staff con plan_id = NULL
                });
            });
        });
        // Filtro condicional por Especialidad Médica (Slug de la tabla specialties)
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

        // Filtro condicional por Búsqueda de Ciudad (Slug de la tabla cities)
        $searchQuery->when($request->city, function ($query) use ($request) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('slug', $request->city);
            });
        });

        // 🔥 EJECUCIÓN CON ORDENACIÓN ALGORÍTMICA TOTALMENTE COMPATIBLE
        $addresses = $searchQuery->orderBy('owner_plan_price', 'desc') 
            ->orderBy('owner_rating', 'desc')                          
            ->get();

        // 2. PROCESAMIENTO HÍBRIDO CON AGRUPACIÓN COMPACTA (REGLA DE ORO)
        $groupedResults = collect();

        foreach ($addresses as $address) {
            $isClinic = !is_null($address->clinic_id);

            if ($isClinic) {
                $clinic = $address->clinic;
                
                // Evitamos duplicar la tarjeta si la clínica ya fue registrada en otra sede
                $existingClinicKey = $groupedResults->search(function ($item) use ($clinic) {
                    return $item['type'] === 'clinic' && $item['id'] === $clinic->id;
                });

                if ($existingClinicKey !== false) {
                    continue;
                }

                $doctorsQuery = $clinic->doctors();
                $clinicBadgeText = "Especialistas en sede";

                if ($request->filled('specialty')) {
                    $doctorsQuery->whereHas('specialties', function ($q) use ($request) {
                        $q->where('slug', $request->specialty);
                    });

                    // 🔒 REGLA DE ORO CLÍNICAS: Buscamos el nombre real de la especialidad buscada para el subtítulo institucional
                    $specialtyModel = Specialty::where('slug', $request->specialty)->first();
                    if ($specialtyModel) {
                        $clinicBadgeText = "Especialistas en {$specialtyModel->name} en sede";
                    }
                }

                $doctorIds = $doctorsQuery->pluck('doctors.id')->toArray();
                $specialistsCount = count($doctorIds);

                $nextAvailableTurn = $this->calculateNextTurn($address->id, $doctorIds, $now, $currentTime);

                $groupedResults->push([
                    'type'        => 'clinic',
                    'id'          => $clinic->id,
                    'title'       => $clinic->name,
                    'subtitle'    => "Sede Principal: {$address->name} • {$address->address}",
                    'slug'        => $clinic->slug,
                    'rating'      => $clinic->rating,
                    'address_id'  => $address->id,
                    'badge_text'  => "{$specialistsCount} {$clinicBadgeText}", 
                    'next_turn'   => $nextAvailableTurn,
                    'user'        => $clinic->user
                ]);
            }
                    else {
                $doctor = $address->doctor;

                // 🔒 BLINDAJE ANTI-REPETIDOS: Buscamos si el doctor ya fue ingresado por otro consultorio previo
                $existingDoctorKey = $groupedResults->search(function ($item) use ($doctor) {
                    return $item['type'] === 'doctor' && $item['id'] === $doctor->id;
                });

                $nextAvailableTurn = $this->calculateNextTurn($address->id, [$doctor->id], $now, $currentTime);

                // 🔒 REGLA DE ORO DOCTORES: Si hay una búsqueda activa de especialidad, fijamos ese nombre en su badge
                if ($request->filled('specialty')) {
                    $specialtyModel = Specialty::where('slug', $request->specialty)->first();
                    $doctorBadgeText = $specialtyModel ? $specialtyModel->name : ucfirst($request->specialty);                    
                } else {
                    $doctorBadgeText = $doctor->specialties->first()->name ?? 'Consultorio Privado';
                }

                if ($existingDoctorKey !== false) {
                    // Si el doctor ya existe en la lista, evaluamos si esta sucursal física tiene un turno más veloz
                    $currentDoctorItem = $groupedResults->get($existingDoctorKey);
                    
                    if ($nextAvailableTurn && (is_null($currentDoctorItem['next_turn']) || $nextAvailableTurn < $currentDoctorItem['next_turn'])) {
                        $currentDoctorItem['next_turn'] = $nextAvailableTurn;
                        $currentDoctorItem['subtitle'] = "Consultorio: {$address->name} • {$address->address}";
                        $currentDoctorItem['address_id'] = $address->id; 
                        $groupedResults->put($existingDoctorKey, $currentDoctorItem);
                    }
                } else {
                    // Es la primera vez que mapeamos al especialista de forma compacta en la lista general
                    $groupedResults->push([
                        'type'        => 'doctor',
                        'id'          => $doctor->id,
                        'title'       => ($doctor->gender === 'female' ? 'Dra. ' : 'Dr. ') . ucfirst($doctor->user->name),
                        'subtitle'    => "Consultorio: {$address->name} • {$address->address}",
                        'slug'        => $doctor->slug,
                        'rating'      => $doctor->rating,
                        'address_id'  => $address->id,
                        'badge_text'  => $doctorBadgeText, 
                        'next_turn'   => $nextAvailableTurn,
                        'user'        => $doctor->user
                    ]);
                }
            }
        } // Cierre definitivo del bucle foreach
        
        // 3. PAGINACIÓN MANUAL SOBRE LA COLECCIÓN COMPACTADA (10 elementos por página)
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        
        $resultsPage = new LengthAwarePaginator(
            $groupedResults->forPage($page, $perPage)->values(),
            $groupedResults->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        $resultsPage->appends($request->all());

        return view('search.index', [
            'results'     => $resultsPage,
            'specialties' => $specialties,
            'cities'      => $cities
        ]);
    }

    /**
     * Función auxiliar privada para encapsular el cálculo de turnos en tiempo real.
     */
    private function calculateNextTurn($addressId, array $doctorIds, $now, $currentTime)
    {
        for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
            $evalDate = Carbon::now()->addDays($dayOffset);
            $evalDayOfWeek = $evalDate->dayOfWeekIso; // 1 = Lunes, 7 = Domingo

            $schedules = DB::table('schedules')->where('address_id', $addressId)->where('day', $evalDayOfWeek)->get();

            foreach ($schedules as $sched) {
                $startTime = Carbon::parse($sched->start_time);
                if ($dayOffset === 0 && $startTime->toTimeString() < $currentTime) {
                    continue;
                }

                $isBooked = Appointment::where('address_id', $addressId)
                    ->whereIn('doctor_id', $doctorIds)
                    ->where('date', $evalDate->toDateString())
                    ->where('start_time', $sched->start_time)
                    ->whereIn('status', ['pending', 'confirmed', 'completed'])
                    ->exists();

                $isUnavailable = Unavailability::whereIn('doctor_id', $doctorIds)
                    ->where('start_date', '<=', $evalDate->toDateString())
                    ->where('end_date', '>=', $evalDate->toDateString())
                    ->exists();

                if (!$isBooked && !$isUnavailable) {
                    return $evalDate->translatedFormat('l d \d\e F') . ' — ' . $startTime->format('g:i A');
                }
            }
        }

        return null;
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
