<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\Address;
use App\Models\Specialty;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Services\ReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use Illuminate\Support\Facades\Log;

class PublicProfileController extends Controller
{        
    private const REVIEWS_PER_PAGE = 5;
    
    /**
     * Etiqueta del próximo turno libre de un médico dentro de una clínica:
     * "Hoy 3:00 PM", "Mañana 8:00 AM" o "Lun 23 nov · 8:00 AM". Sin turnos: "Sin turnos en los próximos 14 días".
     *
     * No usa AvailabilityResolver::nextSlot() a propósito: ese método guarda objetos Slot en caché y puede devolverlos
     * rotos (__PHP_Incomplete_Class). enabledDates() + slotsForDay() son los mismos que usa el calendario.
     */
    private function nextTurnLabel(AvailabilityResolver $resolver, int $doctorId, int $clinicId, Carbon $now): string
    {
        $none = 'Sin turnos en los próximos 14 días';

        try {
            $query = new AvailabilityQuery([$doctorId], $clinicId);
            $dates = array_values($resolver->enabledDates(
                $query,
                $now->copy()->startOfDay(),
                $now->copy()->addDays(14)->startOfDay()
            ));

            foreach (array_slice($dates, 0, 3) as $date) {
                $day = Carbon::parse($date, $now->getTimezone())->startOfDay();

                foreach ($resolver->slotsForDay($query, $day) as $slot) {
                    if (!$slot->isAvailable()) {
                        continue;
                    }

                    $time = $slot->start->format('g:i A');

                    if ($day->isSameDay($now)) {
                        return 'Hoy ' . $time;
                    }
                    if ($day->isSameDay($now->copy()->addDay())) {
                        return 'Mañana ' . $time;
                    }

                    return $day->translatedFormat('D d M') . ' · ' . $time;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('PublicProfile: no se pudo calcular el próximo turno del médico', [
                'doctor_id' => $doctorId,
                'clinic_id' => $clinicId,
                'error'     => $e->getMessage(),
            ]);
        }

        return $none;
    }

    /**
     * Muestra la pantalla del perfil unificado (Clínica o Doctor de opendoctor.online).
     * URL: /medical-partner/{slug}
     */
    public function show(Request $request, $slug)
    {
        $addressId = $request->input('address_id');
        
        // 🔒 BLINDAJE DE TIEMPO REAL: Forzamos el huso horario local de Colombia para erradicar desfases en el calendario
        $tz = 'America/Bogota';
        $now = Carbon::now($tz);
        $todayColombia = $now->toDateString(); // Variable raíz para minDate seguro en Flatpickr
        $currentTime = $now->toTimeString();

        // 🔒 CAPTURA MAESTRA DE LA ESPECIALIDAD REQUERIDA (Soporta Slug e ID numérico)
        $targetSpecialtySlug = $request->input('specialty');
        
        $currentSpecialty = Specialty::where('status', true)
            ->where(function($query) use ($targetSpecialtySlug) {
                $query->where('slug', $targetSpecialtySlug)->orWhere('id', $targetSpecialtySlug);
            })->first();

        // --------------------------------------------------------------------
        // 1. INTENTAR BUSCAR EN EL CATÁLOGO MAESTRO DE CLÍNICAS APROBADAS
        // --------------------------------------------------------------------
        // Clínica aprobada y activa. Si aún no llega al mínimo de médicos aprobados y activos,
        // se muestra una página "próximamente" (sin médicos ni turnos) en lugar de un 404.
        $clinic = Clinic::where('slug', $slug)->first();

        // Clínica que existe pero aún no está habilitada (en revisión o desactivada).
        if ($clinic && ($clinic->validation_status !== 'approved' || !$clinic->active)) {
            return $this->friendlyUnavailable(
                'Esta clínica aún no está disponible',
                'Estamos terminando de preparar el perfil de esta clínica. Vuelve pronto o, mientras tanto, busca otros especialistas.'
            );
        }

        if ($clinic && !$clinic->isListable()) {
            $clinic->load('user');

            return view('public.profiles.clinic_coming_soon', [
                'clinic'         => $clinic,
                'seoTitle'       => $clinic->name . ' | Próximamente en OpenDoctor',
                'seoDescription' => $clinic->name . ' está integrando a su equipo médico en OpenDoctor. Muy pronto podrás agendar aquí.',
                'metaRobots'     => 'noindex, follow',
            ]);
        }

        if ($clinic) {
            $showingAllStaffFallback = false;

            // Determinar si la especialidad solicitada tiene médicos activos en la clínica
            if ($currentSpecialty) {
                $hasDoctorsInSpecialty = $clinic->doctors()
                    ->where('clinic_doctor.status', 'approved')
                    ->where('doctors.validation_status', 'approved')
                    ->where('doctors.active', true)
                    ->whereHas('specialties', function($sp) use ($currentSpecialty) {
                        $sp->where('specialty_id', $currentSpecialty->id);
                    })->exists();

                if (!$hasDoctorsInSpecialty) {
                    $showingAllStaffFallback = true;
                }
            } else {
                $showingAllStaffFallback = true;
            }

            // Cargar relaciones aplicando el Fallback de Especialidades de la clínica
            $clinic->load([
                'user',
                'doctors' => function ($query) use ($currentSpecialty, $showingAllStaffFallback) {
                    $query->where('clinic_doctor.status', 'approved')
                          ->where('doctors.validation_status', 'approved')
                          ->where('doctors.active', true)
                          ->when(!$showingAllStaffFallback, function($q) use ($currentSpecialty) {
                              $q->whereHas('specialties', function($sp) use ($currentSpecialty) {
                                  $sp->where('specialty_id', $currentSpecialty->id);
                              });
                          })
                          ->with(['user', 'specialties']);
                },
                'addresses' => function ($query) use ($addressId) {
                    $query->where('status', true)->with(['city', 'services' => fn($srv) => $srv->where('services.active', true)]);
                    if ($addressId) {
                        $query->where('id', $addressId);
                    }
                }
            ]);

            // Determinar la sede física de trabajo evaluada
            $address = $addressId 
                ? $clinic->addresses->where('id', $addressId)->first() 
                : $clinic->addresses->first();

            if (!$address) {
                return $this->friendlyUnavailable(
                    'Esta sede no está disponible por ahora',
                    'No pudimos mostrarte esa sede en este momento. Puedes buscar otros especialistas o volver más tarde.'
                );
            }

            if ($showingAllStaffFallback && $clinic->doctors->isNotEmpty()) {
                $currentSpecialty = $clinic->doctors->first()->specialties->first();
            }

            // El próximo turno de cada médico sale del MISMO motor que usan el calendario y la reserva
            // (bloqueos, cupo diario, anticipación, duración del servicio y sedes de ESTA clínica).
            $unifiedSlots = [];
            $resolver = app(AvailabilityResolver::class);
            $nextTurnByDoctor = [];
            foreach ($clinic->doctors as $doc) {
                $nextTurnByDoctor[$doc->id] = $this->nextTurnLabel($resolver, (int) $doc->id, (int) $clinic->id, $now);
            }

                        // FÓRMULA DE SÍNTESIS DEPURADA: Prepara las tarjetas moleculares para la clínica
            $results = [];
            $clinicAddresses = $clinic->addresses;

            foreach ($clinic->doctors as $doc) {
                // Buscamos en qué sedes de ESTA clínica tiene horarios reales configurados este médico
                $activeAddressIds = DB::table('schedules')
                    ->where('doctor_id', $doc->id)
                    ->whereIn('address_id', $clinicAddresses->pluck('id'))
                    ->pluck('address_id')
                    ->unique()
                    ->toArray();
                
                $assignedAddressId = !empty($activeAddressIds) ? $activeAddressIds[0] : $address->id;
                $targetAddressModel = $clinicAddresses->firstWhere('id', $assignedAddressId) ?? $address;

                $results[] = [
                    'id'                 => $doc->id,
                    'slug'               => $doc->slug ?? $doc->user->slug,
                    'type'               => 'doctor',
                    'title'              => ($doc->gender === 'female' ? 'Dra. ' : 'Dr. ') . ucfirst($doc->user->name),
                    'subtitle'           => (preg_match('/^sede\b/iu', trim((string) $targetAddressModel->name)) ? '' : 'Sede ') . $targetAddressModel->name . " • " . $targetAddressModel->address,
                    'rating'             => $doc->rating ?? 5,
                    'address_id'         => (int)$assignedAddressId, 
                    'active_address_ids' => !empty($activeAddressIds) ? $activeAddressIds : [$address->id],
                    'badge_text'         => $doc->specialties->first()->name ?? ($currentSpecialty ? $currentSpecialty->name : 'Especialista'),
                    'relationship'       => $doc->pivot->relationship ?? 'invited',
                    'next_turn'          => $nextTurnByDoctor[$doc->id] ?? 'Sin turnos en los próximos 14 días',
                    'user'               => $doc->user
                ];
            }

            session(['current_clinic_user_id' => $clinic->user_id]);
            session()->forget('current_doctor_id');
            
            return view('public.clinic_decision', [
                'clinic'                  => $clinic,
                'specialty'               => $currentSpecialty,
                'clinicAddresses'         => $clinicAddresses,
                'results'                 => $results,
                'address'                 => $address,
                'unifiedSlots'            => $unifiedSlots,
                'showingAllStaffFallback' => $showingAllStaffFallback,
                'seoTitle'                => $clinic->name . ' | Selección de Turno',
                'seoDescription'          => $clinic->bio ?? 'Elige entre atención inmediata o tu especialista preferido.'
            ]);
        }
        // --------------------------------------------------------------------
        // 2. SI NO ES UNA CLÍNICA, BUSCAMOS EN EL CATÁLOGO DE ESPECIALISTAS INDEPENDIENTES
        // --------------------------------------------------------------------
        $doctor = Doctor::where('slug', $slug)
            ->where('validation_status', 'approved')
            ->where('active', true)
            ->first();

        if (!$doctor) {
            return $this->friendlyUnavailable(
                'No encontramos este perfil',
                'Es posible que el enlace haya cambiado o que este perfil no esté disponible por ahora. Te ayudamos a encontrar otro especialista.'
            );
        }

        $fromClinicId = $request->input('from_clinic') ?? $request->input('clinic_id');
        $preSelectedAddress = null;

        // Carga base unificada de datos del médico y sus especialidades ordenadas
        $doctor->load([
            'user',
            'specialties' => function ($query) use ($targetSpecialtySlug) {
                if ($targetSpecialtySlug && isset($targetSpecialtySlug)) {
                    $query->orderByRaw('CASE WHEN specialties.id = ? THEN 0 ELSE 1 END', [$targetSpecialtySlug])
                          ->orderBy('name', 'asc');
                } else {
                    $query->orderBy('name', 'asc');
                }
            }
        ]);

        if (!$currentSpecialty) {
            $currentSpecialty = $doctor->specialties->first() ?? (object) [
                'id' => 1, 'name' => 'Consulta General', 'slug' => 'general'
            ];
        }

        // =========================================================================
        // 🏢 CASO 1: EL MÉDICO ESTÁ OPERANDO COMO STAFF DE UNA CLÍNICA (?from_clinic=1)
        // =========================================================================
        if ($fromClinicId) {
            $parentClinic = Clinic::listable()->where('clinics.id', $fromClinicId)->first();

            if (!$parentClinic) {
                return $this->friendlyUnavailable(
                    'Esta institución no está disponible por ahora',
                    'Estamos preparando su equipo médico. Mientras tanto, puedes buscar otros especialistas.'
                );
            }

            // El médico debe ser miembro aprobado de ESA clínica (no basta con pasar ?from_clinic=).
            $isMember = DB::table('clinic_doctor')
                ->where('clinic_id', $parentClinic->id)
                ->where('doctor_id', $doctor->id)
                ->where('status', 'approved')
                ->exists();

            if (!$isMember) {
                return $this->friendlyUnavailable(
                    'Este especialista no atiende aquí por ahora',
                    'No encontramos disponibilidad de este especialista en esa institución. Puedes buscar otras opciones.'
                );
            }

            // Traer únicamente las sedes oficiales de la clínica
            $clinicAddresses = Address::where('status', true)->where('clinic_id', $fromClinicId)->get();

            // Detección híbrida: inyectamos servicios institucionales a cada sede
            foreach ($clinicAddresses as $currentAddr) {
                if ($currentAddr->type === 'virtual') {
                    $rawServices = DB::table('address_service')
                        ->join('services', 'address_service.service_id', '=', 'services.id')
                        ->where('address_service.address_id', $currentAddr->id)
                        ->where('services.active', true)
                        ->select('services.id', 'services.name', 'address_service.price', 'address_service.duration')
                        ->distinct()->get();
                } else {
                    $rawServices = DB::table('address_service')
                        ->join('services', 'address_service.service_id', '=', 'services.id')
                        ->join('service_specialty', 'services.id', '=', 'service_specialty.service_id')
                        ->join('doctor_specialty', 'service_specialty.specialty_id', '=', 'doctor_specialty.specialty_id')
                        ->where('address_service.address_id', $currentAddr->id)
                        ->where('services.active', true)
                        ->where('doctor_specialty.doctor_id', $doctor->id)
                        ->select('services.id', 'services.name', 'address_service.price', 'address_service.duration')
                        ->distinct()->get();
                }

                $formattedServices = $rawServices->map(function($srv) {
                    $srv->pivot = (object)['price' => $srv->price, 'duration' => $srv->duration];
                    return $srv;
                });

                $currentAddr->setRelation('services', $formattedServices);
            }

            $doctor->setRelation('addresses', $clinicAddresses);
            session(['current_clinic_user_id' => $parentClinic->user_id]);
            session(['current_doctor_id' => $doctor->user_id]);
        }
        // =========================================================================
        // 🩺 CASO 2: EL MÉDICO OPERA EN SU CONSULTORIO PARTICULAR AUTÓNOMO
        // =========================================================================
        else {
            $doctor->load(['addresses' => function ($query) use ($doctor) {
                $query->where('status', true)
                    ->whereNull('clinic_id')
                    ->with(['city', 'services' => function($query) use ($doctor) {
                        $query->where('services.active', true)
                            ->withPivot('price', 'duration')
                            ->whereIn('services.id', function($subQuery) use ($doctor) {
                                $subQuery->select('service_specialty.service_id')
                                    ->from('service_specialty')->where('service_specialty.user_id', $doctor->user_id);
                            });
                    }]);
            }]);

            session(['current_doctor_id' => $doctor->user_id]);
            session()->forget('current_clinic_user_id');
        }
        // =========================================================================
        // 🔒 ALGORITMO HÍBRIDO AVANZADO: PREPARACIÓN DEL HORIZONTE PREDICTIVO
        // =========================================================================
        $nextAvailableDate = $todayColombia; // Forzado de tiempo seguro local
        $targetAddressId = null;

        if ($addressId) {
            $targetAddressId = (int)$addressId;
        } elseif ($doctor->addresses->isNotEmpty()) {
            $targetAddressId = (int)$doctor->addresses->first()->id;
        }

        $enabledDates = [];
        $enabledDays = [];
        $enabledDatesByAddress = [];
        $nextDateByAddress = [];

        if ($targetAddressId) {
            $settings = DB::table('doctor_settings')->where('doctor_id', $doctor->id)->first();
            $maxAdvanceDays = (int) ($settings->max_advance_days ?? 30);
            $maxDate = $now->copy()->addDays($maxAdvanceDays);

            // Cada sede tiene sus propios horarios: calculamos los días con turno libre de CADA sede del médico,
            // para que la página use la lista de la sede que el paciente elija (antes era solo la de la primera sede).
            $datesForAddress = function (int $aid) use ($doctor, $fromClinicId, $now, $maxDate) {
                try {
                    return app(AvailabilityResolver::class)->enabledDates(
                        new AvailabilityQuery([(int) $doctor->id], $fromClinicId ? (int) $fromClinicId : null, $aid),
                        $now->copy()->startOfDay(),
                        $maxDate->copy()->startOfDay()
                    );
                } catch (\Throwable $e) {
                    Log::error('PublicProfile: falló el motor de disponibilidad', [
                        'doctor_id'  => $doctor->id,
                        'address_id' => $aid,
                        'error'      => $e->getMessage(),
                    ]);
                    return [];
                }
            };

            $addressIdsToCompute = $doctor->addresses->pluck('id')->map(fn ($id) => (int) $id)->push((int) $targetAddressId)->unique()->values();

            foreach ($addressIdsToCompute as $aid) {
                $dates = array_values($datesForAddress($aid));
                $enabledDatesByAddress[$aid] = $dates;
                $nextDateByAddress[$aid] = $dates[0] ?? null;
            }

            // Días de la semana con horario en la sede objetivo (0=Dom, 1=Lun ... 6=Sab) para pintar el calendario.
            $enabledDays = DB::table('schedules')
                ->where('address_id', $targetAddressId)
                ->where('doctor_id', $doctor->id)
                ->pluck('day')
                ->map(fn ($day) => (int) $day === 7 ? 0 : (int) $day)
                ->unique()
                ->values()
                ->toArray();

            // Compatibilidad: la lista "global" sigue siendo la de la sede objetivo.
            $enabledDates = $enabledDatesByAddress[(int) $targetAddressId] ?? [];

            if ($addressId) {
                $nextAvailableDate = $nextDateByAddress[(int) $targetAddressId] ?? $todayColombia;
            } else {
                // Sin sede elegida: arrancamos en el primer día con turnos entre TODAS las sedes.
                $candidates = array_filter($nextDateByAddress);
                $nextAvailableDate = $candidates ? min($candidates) : $todayColombia;
            }
        }

        if ($addressId) {
            $preSelectedAddress = $doctor->addresses->where('id', $addressId)->first();
        }

        $langNames = ['es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués', 'fr' => 'Francés', 'de' => 'Alemán'];
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
                        
        // 🔒 DESPACHO INTEGRAL CON RETORNO SEGURO A PRODUCCIÓN
        return view('public.public-profile', [
            'partner'            => $doctor,
            'profileType'        => 'doctor',
            'currentSpecialty'   => $currentSpecialty,
            'languages'          => $languages,
            'seoTitle'           => "Dr(a). " . ucfirst($doctor->user->name) . ' | Agendamiento en Línea',
            'seoDescription'     => $doctor->bio ?? 'Especialista profesional calificado, agenda tu cita médica en línea.',
            'metaRobots'         => 'index, follow',
            'preSelectedAddress' => $preSelectedAddress,
            'fromClinicId'       => $fromClinicId,
            'nextAvailableDate'  => $nextAvailableDate,
            'enabledDates'       => $enabledDates,
            'enabledDatesByAddress' => $enabledDatesByAddress,
            'enabledDays'        => $enabledDays ?? [],  // Entrega los índices numéricos para los fondos grises clickeables
            'todayColombia'      => $todayColombia       // Fuerza el minDate estricto contra el ayer real en el frontend
        ]);
    }

    /**
     * Respuesta amable cuando un perfil no se puede mostrar. Mantiene el código 404 (bueno para SEO)
     * pero con una página cálida, sin mensajes técnicos ni apariencia de error del sistema.
     */
    private function friendlyUnavailable(string $title, string $message)
    {
        return response()->view('public.profiles.unavailable', [
            'title'      => $title,
            'message'    => $message,
            'seoTitle'   => $title . ' | OpenDoctor',
            'metaRobots' => 'noindex, follow',
        ], 404);
    }

    /**
     * Muestra el perfil público premium de un médico especialista a través de su slug.
     */
    public function showProfile(string $slug, ReviewService $reviewService)
    {
        $doctor = $this->findPublicDoctor($slug);

        if (!$doctor) {
            return $this->friendlyUnavailable(
                'No encontramos este perfil',
                'Es posible que el enlace haya cambiado o que este perfil no esté disponible por ahora. Te ayudamos a encontrar otro especialista.'
            );
        }

        $clinicAddresses = $this->clinicAddressesFor($doctor);

        // Solo reseñas publicadas, paginadas y con el nombre del autor (sin N+1)
        $reviews = $doctor->publishedReviews()
            ->with('user:id,name')
            ->latest()
            ->latest('id')
            ->paginate(self::REVIEWS_PER_PAGE, pageName: 'resenas')
            ->fragment('resenas');

        return view('public.partner-profile', [
            'doctor'          => $doctor,
            'clinicAddresses' => $clinicAddresses,
            'reviews'         => $reviews,
            'reviewSummary'   => $reviewService->summary($doctor),
            'canReview'       => $reviewService->canReview(auth()->user(), $doctor),
        ]);
    }

    /**
     * Doctor visible públicamente: activo y con validación aprobada.
     */
    private function findPublicDoctor(string $slug): ?Doctor
    {
        return Doctor::query()
            ->with([
                'user',
                'specialties',
                'settings',
                'expertises',
                'gallery',
                'clinics'   => fn ($query) => $query->wherePivot('status', 'approved'),
                // Sedes propias activas (SoftDeletes ya excluye las eliminadas) con sus servicios independientes
                'addresses' => fn ($query) => $query->bookable()->with(['city', 'services']),
            ])
            ->where('slug', $slug)
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->first();
    }

    /**
     * Sedes físicas de clínicas aprobadas donde el doctor tiene horarios activos.
     *
     * @return Collection<int, Address>
     */
    private function clinicAddressesFor(Doctor $doctor): Collection
    {
        $clinicAddressIds = DB::table('schedules as s')
            ->join('addresses as a', 'a.id', '=', 's.address_id')
            ->join('clinic_doctor as cd', function ($join) {
                $join->on('cd.clinic_id', '=', 'a.clinic_id')->on('cd.doctor_id', '=', 's.doctor_id');
            })
            ->where('s.doctor_id', $doctor->id)
            ->where('s.is_active', 1)
            ->where('cd.status', 'approved')
            ->whereIn('a.clinic_id', Clinic::listable()->pluck('clinics.id'))
            ->pluck('a.id')
            ->unique();

        return Address::query()
            ->bookable()
            ->where('type', 'physical')
            ->whereIn('id', $clinicAddressIds)
            ->with(['city', 'clinic'])
            ->get();
    }

    /**
     * Guarda el mensaje de contacto del perfil público de un doctor.
     */
    public function sendContactMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id'       => ['required', 'integer', 'exists:doctors,id'],
            'client_name'     => ['required', 'string', 'max:100'],
            'client_email'    => ['required', 'email:rfc', 'max:150'],
            'client_phone'    => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s()\-]{7,30}$/'],
            'message_content' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'client_name.required'     => 'Escribe tu nombre completo.',
            'client_email.required'    => 'Escribe tu correo electrónico.',
            'client_email.email'       => 'El correo electrónico no es válido.',
            'client_phone.regex'       => 'El teléfono no tiene un formato válido.',
            'message_content.required' => 'Escribe tu mensaje.',
            'message_content.min'      => 'El mensaje debe tener al menos 10 caracteres.',
            'message_content.max'      => 'El mensaje no puede superar los 1000 caracteres.',
            'doctor_id.exists'         => 'El especialista no existe.',
        ]);

        $doctor = Doctor::with('user')->findOrFail($validated['doctor_id']);

        // Sanitización
        $name    = $this->sanitizeText($validated['client_name']);
        $email   = mb_strtolower(trim($validated['client_email']));
        $phone   = $this->sanitizeText($validated['client_phone'] ?? '');
        $content = $this->sanitizeText($validated['message_content'], true);

        $doctorTitle = $doctor->gender === 'female' ? 'Dra.' : 'Dr.';
        $doctorName  = $this->sanitizeText($doctorTitle . ' ' . ($doctor->user->name ?? ''));

        // Todo el contexto va dentro del campo "message"
        $message = implode("\n", [
            '[Contacto desde perfil público]',
            "Doctor ID: {$doctor->id}",
            "Doctor: {$doctorName}",
            "Nombre: {$name}",
            "Correo: {$email}",
            'Teléfono: ' . ($phone !== '' ? $phone : 'No indicado'),
            '',
            'Mensaje:',
            $content,
        ]);

        try {
            ContactMessage::create([
                'name'    => $name,
                'email'   => $email,
                'subject' => Str::limit("Contacto perfil público - {$doctorName} (ID {$doctor->id})", 190, ''),
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error guardando contact_message del perfil público', [
                'doctor_id' => $doctor->id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No pudimos enviar tu mensaje. Inténtalo de nuevo en unos minutos.',
            ], 500);
        }

        return response()->json(['message' => 'Mensaje enviado correctamente.'], 201);
    }

    /**
     * Limpia texto de usuario: sin HTML, sin caracteres de control, espacios normalizados.
     * Si $keepNewlines es true, conserva los saltos de línea (para el mensaje).
     */
    private function sanitizeText(?string $value, bool $keepNewlines = false): string
    {
        $value = strip_tags((string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = str_replace("\r\n", "\n", $value);

        if ($keepNewlines) {
            $value = preg_replace("/[ \t]+/", ' ', $value);
            $value = preg_replace("/\n{3,}/", "\n\n", $value);
        } else {
            $value = preg_replace('/\s+/', ' ', $value);
        }

        return trim($value ?? '');
    }
}