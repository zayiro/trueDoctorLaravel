<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Specialty;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\ClinicInvitationNotification;
use App\Notifications\DirectDoctorWelcomeNotification;
use App\Notifications\InvitationToIndependentDoctorNotification;

class ClinicDoctorController extends Controller
{    
    /**
     * Muestra la nómina de médicos vinculados a la clínica actual.
     */
    public function index()
    {
        if (auth()->user()->role !== 'clinic') {
            abort(403, 'Acceso exclusivo para centros médicos.');
        }

        $clinic = auth()->user()->clinic;

        // Listamos los doctores vinculados cruzando la tabla pivote
        $doctors = $clinic->doctors()
            ->with(['user', 'specialties']) 
            ->withPivot('status', 'created_at')
            ->orderBy('clinic_doctor.created_at', 'desc')
            ->get();

        // Catálogo global para alimentar la lista dinámica de registro directo
        $specialties = Specialty::where('status', true)
            ->orderBy('name', 'asc')
            ->get();

        return view('partner.clinic.doctors.index', compact('doctors', 'clinic', 'specialties'));
    }

    /**
     * Procesa la vinculación: Decide si es invitación o registro masivo directo.
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'clinic') {
            abort(403);
        }

        $clinic = auth()->user()->clinic;
        $actionType = $request->input('action_type', 'invite');

        if ($actionType === 'invite') {
            // El control de límite unitario se procesa aquí
            if (!$clinic->canAddMoreDoctors()) {
                return back()->with('error', 'Has alcanzado el límite máximo de médicos permitidos.');
            }
            return $this->handleInvitationFlow($request, $clinic);
        }

        if ($actionType === 'register_direct') {
            return $this->handleDirectRegistrationFlow($request, $clinic);
        }

        abort(400, 'Acción no soportada.');
    }
    /**
     * FLUJO 1: Invitar a un doctor existente por su número de identificación.
     */
    protected function handleInvitationFlow(Request $request, Clinic $clinic)
    {        
        $request->validate([
            'identification' => 'required|string',
        ], [
            'identification.required' => 'Debes ingresar el número de cédula o identificación del médico.',
        ]);

        $doctor = Doctor::where('identification', trim($request->identification))->first();

        if (!$doctor) {
            return back()->with('error', 'No se encontró ningún médico registrado con ese ID. Si es propio tuyo, usa "Registrar Nuevo Médico".')->withInput();
        }

        $alreadyLinked = $clinic->doctors()->where('doctor_id', $doctor->id)->exists();
        
        if ($alreadyLinked) {
            return back()->with('error', 'El especialista seleccionado ya se encuentra en tu nómina o tiene un proceso activo.');
        }

        // Se guarda en estado 'pending' a la espera de que el médico acepte
        $clinic->doctors()->attach($doctor->id, ['status' => 'pending']);

        $doctor->user->notify(new ClinicInvitationNotification($clinic));

        return redirect()->route('partner.clinic.doctors.index')
            ->with('success', "Solicitud enviada con éxito al Dr/a. {$doctor->user->name}.");
    }
    
    /**
     * FLUJO 2: Registrar múltiples médicos nuevos en lote usando la cédula como clave.
     */
    protected function handleDirectRegistrationFlow(Request $request, Clinic $clinic)
    {                
        $request->validate([
            'doctors'                   => 'required|array|min:1',
            'doctors.*.name'            => 'required|string|max:255',
            'doctors.*.email'           => 'required|string|email|max:255',
            'doctors.*.identification'  => 'required|string|max:255',
            'doctors.*.medical_license' => 'required|string|max:255',
            'doctors.*.phone'           => 'required|string|max:10',
            'doctors.*.gender'          => 'required|in:male,female,other',            
            'doctors.*.specialties'     => 'required|array|min:1',
            'doctors.*.specialties.*'   => 'exists:specialties,id',
        ]);

        $doctorsData = $request->input('doctors');
        $registeredSuccessfully = [];
        $alreadyExisted = [];

        if (!$clinic->canAddMoreDoctors()) {
            return back()->with('error', 'Has alcanzado el límite máximo de médicos permitidos en tu plan actual. Realiza un upgrade.')->withInput();
        }

        try {
            DB::transaction(function () use ($doctorsData, $clinic, &$registeredSuccessfully, &$alreadyExisted) {
                // El bucle de inserción se detalla en la Parte B...
                foreach ($doctorsData as $docData) {
                    // Verificación preventiva anti-duplicados global en el SaaS
                    $userExists = User::where('email', trim($docData['email']))->exists();
                    $doctorExists = Doctor::where('identification', trim($docData['identification']))->exists();

                    if ($userExists || $doctorExists) {
                        $alreadyExisted[] = [
                            'name' => $docData['name'],
                            'email' => $docData['email'],
                            'reason' => $userExists ? 'El correo ya está en uso.' : 'La identificación ya está registrada.'
                        ];
                        continue;
                    }

                    // BLINDAJE MULTI-TENANT: Validar límites en tiempo real (Pessimistic Locking)
                    $currentCount = DB::table('clinic_doctor')->where('clinic_id', $clinic->id)->lockForUpdate()->count();

                    // USAMOS TU RELACIÓN NATIVA CON OPERADOR OPCIONAL (?->) POR SEGURIDAD
                    $maxDoctors = $clinic->plan?->max_doctors ?? 0; 

                    if (($currentCount + count($registeredSuccessfully)) >= $maxDoctors) {
                        throw new \Exception('Has alcanzado el límite máximo de médicos permitidos en tu plan actual.');
                    }

                    // A. Crear Usuario usando su ID como Contraseña inicial
                    $user = User::create([
                        'name'     => trim($docData['name']),
                        'email'    => strtolower(trim($docData['email'])),
                        'password' => Hash::make(trim($docData['identification'])), 
                        'role'     => 'doctor',
                    ]);
                    
                    // ESTO ES LO QUE LE ASIGNA EL ROL EN SPATIE:
                    $role = Role::firstOrCreate(['name' => 'doctor']);
                    $user->assignRole($role);

                    // B. Crear Perfil de Especialista (Gatilla tu Observer para settings y sedes)                    
                    do {
                        $slug = Str::slug($docData['name']) . '-' . strtoupper(Str::random(5));
                    } while (Doctor::where('slug', $slug)->exists()); // Evita duplicados en el ecosistema
                    
                    $cleanPhone = preg_replace('/[^0-9]/', '', trim($docData['phone']));
                    $fullPhone = $docData['country_code'] ? $docData['country_code'] . $cleanPhone : '+57' . $cleanPhone;

                    $doctor = Doctor::create([
                        'slug'              => $slug,
                        'user_id'           => $user->id,
                        'medical_license'   => trim($docData['medical_license']),
                        'identification'    => trim($docData['identification']),
                        'gender'            => $docData['gender'],
                        'phone'             => $fullPhone,
                        'validation_status' => 'approved', 
                        'active'            => true,
                    ]);

                    // Guardar las especialidades en la tabla pivote doctor_specialty
                    $doctor->specialties()->attach($docData['specialties']);

                    // C. Vincular directamente a la clínica como aprobado
                    $clinic->doctors()->attach($doctor->id, ['status' => 'approved']);

                    //validar si esta linea de codigo hace que se envie dos veces el correo al doctor
                    //ya que el userObserver tambien envia un email de bienvenida
                    
                    $user->notify(new DirectDoctorWelcomeNotification($clinic));
                    $registeredSuccessfully[] = $docData['name'];
                }
            }); // Fin del DB::transaction
            
            $response = redirect()->route('partner.clinic.doctors.index');

            if (count($registeredSuccessfully) > 0) {
                $response->with('success', "Se han registrado exitosamente " . count($registeredSuccessfully) . " nuevo(s) especialista(s).");
            }

            if (count($alreadyExisted) > 0) {
                $response->with('skipped_doctors', $alreadyExisted);
            }

            return $response;

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
    
    /**
     * Modifica el estado operativo del médico dentro de la clínica (Activar/Desactivar).
     */
    public function toggleStatus(Doctor $doctor)
    {
        if (auth()->user()->role !== 'clinic') abort(403);
        $clinic = auth()->user()->clinic;

        $pivot = $clinic->doctors()->where('doctor_id', $doctor->id)->first()?->pivot;
        if (!$pivot || $pivot->status === 'pending') {
            return back()->with('error', 'Acción no permitida o relación inexistente.');
        }

        $newStatus = $pivot->status === 'approved' ? 'inactive' : 'approved';
        $clinic->doctors()->updateExistingPivot($doctor->id, ['status' => $newStatus]);

        if ($newStatus === 'inactive') {
            DB::table('schedules')->where('doctor_id', $doctor->id)->whereIn('address_id', function ($query) use ($clinic) {
                $query->select('id')->from('addresses')->where('clinic_id', $clinic->id)->whereNull('deleted_at');
            })->delete();
        }

        return back()->with('success', "El especialista ha cambiado de estado en la nómina corporativa.");
    }

    /**
     * Remueve a un médico de la nómina corporativa, migra su cuenta al plan Free e invita al flujo independiente.
     */
    public function destroy(Doctor $doctor)
    {        
        // 1. Control de acceso: Solo el rol tipo clínica puede ejecutar esta acción
        if (auth()->user()->role !== 'clinic') {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }
        
        $clinic = auth()->user()->clinic;

        // 2. Validación normativa de negocio: Evitar dejar citas huérfanas en el sistema
        $hasAppointments = Appointment::where('clinic_id', $clinic->id)
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('date', '>=', now()->toDateString())
            ->exists();

        if ($hasAppointments) {
            return back()->with('error', 'No puedes desvincular al médico, posee citas agendadas vigentes.');
        }
        // 3. Evaluar el origen del médico: ¿No tiene registro de configuración o su plan_id es NULL?
        $doctorSettings = DB::table('doctor_settings')
            ->where('doctor_id', $doctor->id)
            ->first();
        
        $shouldMigrateToFree = false;
        
        // REGLA CORREGIDA: Si no existe el registro O si existe con plan_id nulo
        if (!$doctorSettings || is_null($doctorSettings->plan_id)) {
            $shouldMigrateToFree = true;
        }
        // 4. Procesamiento atómico de la desvinculación y migración de perfil
        DB::transaction(function () use ($clinic, $doctor, $shouldMigrateToFree) {
            
            if ($shouldMigrateToFree) {
                // A. Notificación por email para jalarlo como independiente
                if ($doctor->user) {
                    $doctor->user->notify(new InvitationToIndependentDoctorNotification($clinic->name));
                }

                // B. Ajuste en doctor_settings (updateOrInsert asegura que la fila exista con Plan Free ID = 1)                
                DB::table('doctor_settings')
                    ->updateOrInsert(
                        ['doctor_id' => $doctor->id], // Condición para buscar si ya existe
                        [
                            'doctor_id'  => $doctor->id, // <--- OBLIGATORIO: Añadirlo aquí para que no sea NULL si se inserta desde cero
                            'plan_id'    => 1,
                            'updated_at' => now()
                        ]
                    );
                    

                // C. Blindaje Normativo: Cambiar estado a 'missing' para obligar carga de documentos
                DB::table('doctors')
                    ->where('id', $doctor->id)
                    ->update([
                        'validation_status' => 'missing',
                        'updated_at' => now()
                    ]);
            }

            // 5. Desvincular al médico de la relación de la clínica (Tabla pivote)
            $clinic->doctors()->detach($doctor->id);

            // 6. Eliminar grilla de horarios asignada a las sedes de esta clínica
            DB::table('schedules')
                ->where('doctor_id', $doctor->id)
                ->whereIn('address_id', function ($query) use ($clinic) {
                    $query->select('id')->from('addresses')->where('clinic_id', $clinic->id);
                })->delete();
        });
        // 7. Redirección final con confirmación visual en la interfaz de la clínica
        return back()->with('success', 'Especialista removido de la nómina corporativa correctamente.');
    }

    /**
     * Reenvía la invitación por correo electrónico a un médico aliado.
     */
    public function resendInvitation(Doctor $doctor)
    {
        if (auth()->user()->role !== 'clinic') abort(403);
        $clinic = auth()->user()->clinic;
        
        $isLinked = $clinic->doctors()->where('doctor_id', $doctor->id)->where('clinic_doctor.status', 'pending')->exists();
        if (!$isLinked) return redirect()->back()->with('error', 'No se encontró invitación pendiente.');

        $doctor->user->notify(new ClinicInvitationNotification($clinic));
        return redirect()->back()->with('success', 'Invitación reenviada con éxito.');
    }
}
