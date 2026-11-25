<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Specialty;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

class RegisterClinicController extends Controller
{
    /** Indicativo del formulario => código de país que se guarda en clinics.country_code. */
    private const COUNTRIES = ['+57' => 'CO'];

    /**
     * Muestra el formulario de registro para centros médicos y clínicas.
     */
    public function register() 
    {
        $specialties = Specialty::orderBy('name', 'asc')->get();

        return view('auth.register-clinic', compact('specialties'));
    }

    /**
     * Procesa la creación transaccional de la clínica, su rol en Spatie y su plan institucional.
     * Esta página es pública: ante cualquier fallo el usuario ve un mensaje amable y el detalle técnico va al log.
     */
    public function store(Request $request)
    {
        // Freno básico contra registros masivos (se cuenta cada envío, válido o no).
        $limiterKey = 'register-clinic:' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, 8)) {
            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'Recibimos varios intentos desde tu conexión. Espera unos minutos y vuelve a intentarlo.');
        }
        RateLimiter::hit($limiterKey, 600);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nit' => [
                'required',
                'string',
                'min:7',
                'max:20',
                'regex:/^[0-9]+(-[0-9]{1})?$/',
                // El NIT se guarda sin guion: se compara ya limpio (y contra lo que haya, incluso borrado), no tal como se escribió.
                function ($attribute, $value, $fail) {
                    $clean = str_replace('-', '', trim((string) $value));
                    if (DB::table('clinics')->where('nit', $clean)->orWhere('nit', trim((string) $value))->exists()) {
                        $fail('Este NIT ya está registrado en nuestra plataforma.');
                    }
                },
            ],
            'reps_code' => [
                'required',
                'string',
                'digits:10', 
                Rule::unique('clinics', 'reps_code'), 
            ],
            'country_code' => ['required', Rule::in(array_keys(self::COUNTRIES))],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'], 
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'specialties' => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
        ], [
            'nit.regex' => 'El formato del NIT debe ser solo números o incluir el dígito de verificación (ej: 12345678-9).',
            'phone.regex' => 'El número celular debe contener exactamente 10 dígitos numéricos (ej: 3001234567).',
            'country_code.in' => 'Selecciona un indicativo de país válido.',
            'email.unique' => 'Este correo ya tiene una cuenta. Inicia sesión o usa otro correo.',
            'reps_code.required' => 'El código de habilitación REPS es obligatorio.',
            'reps_code.digits' => 'El código REPS debe tener exactamente 10 números.',
            'reps_code.unique' => 'Este código REPS ya se encuentra registrado.',
        ]);        

        $cleanPhone = preg_replace('/[^0-9]/', '', trim($request->phone));
        $cleanNit = str_replace('-', '', trim($request->nit));

        $fullPhone = $request->country_code . $cleanPhone;
        $countryIso = self::COUNTRIES[$request->country_code];

        try {
            $user = null;

            // Estructura transaccional unificada para prevenir inconsistencias en el SaaS
            DB::transaction(function () use ($request, $cleanNit, $fullPhone, $countryIso, &$user) {
                
                // 1. Crear el usuario administrador del centro médico
                $user = User::create([
                    'name'     => trim($request->name),
                    'email'    => strtolower(trim($request->email)),
                    'password' => Hash::make($request->password),
                    'role'     => 'clinic',
                ]);

                // 2. Sincronización blindada de permisos con Spatie
                $role = Role::firstOrCreate(['name' => 'clinic']);                
                $user->assignRole($role);

                // 3. Crear el perfil de la clínica
                // Al ejecutarse este create(), Laravel dispara el ClinicObserver para inyectar ClinicSetting y la Sede Virtual
                $clinic = $user->clinic()->create([
                    'nit'               => $cleanNit,
                    'reps_code'         => trim($request->reps_code), 
                    'phone'             => $fullPhone,
                    'validation_status' => 'missing', 
                    'active'            => true
                ]);

                // País de la clínica (columna aparte del indicativo del teléfono)
                DB::table('clinics')->where('id', $clinic->id)->update(['country_code' => $countryIso]);

                // El observador no relanza sus errores: si no dejó la configuración (con su plan), se deshace todo el registro.
                if (!DB::table('clinic_settings')->where('clinic_id', $clinic->id)->whereNotNull('plan_id')->exists()) {
                    throw new RuntimeException('La clínica quedó sin configuración inicial (clinic_id ' . $clinic->id . ').');
                }
                
                // 4. Guardar las especialidades en la tabla pivote clinic_specialty
                $clinic->specialties()->attach($request->specialties);
            });

            // Correo de verificación (no bloquea el acceso; si el correo falla, el registro ya quedó hecho).
            try {
                event(new Registered($user));
            } catch (Throwable $e) {
                Log::warning('clinic.register_verification_mail_failed', ['user_id' => $user?->id, 'message' => $e->getMessage()]);
            }

            return redirect()->route('login')
                ->with('success', 'Clínica registrada correctamente. Ya puedes iniciar sesión para administrar tu centro médico. Te enviamos un correo para verificar tu dirección.');

        } catch (QueryException $e) {
            Log::warning('clinic.register_query_failed', ['sqlstate' => $e->getCode(), 'message' => $e->getMessage()]);

            // 23000: dato único repetido que se coló entre la validación y el guardado (dos envíos casi simultáneos).
            $msg = $e->getCode() === '23000'
                ? 'Parece que este NIT, código REPS o correo ya está registrado. Si es tu clínica, inicia sesión; si no, revisa los datos.'
                : 'No pudimos completar el registro en este momento. Inténtalo de nuevo en unos minutos.';

            return redirect()->back()->withInput($request->except('password', 'password_confirmation'))->with('error', $msg);
        } catch (Throwable $e) {
            Log::error('clinic.register_failed', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'No pudimos completar el registro en este momento. Inténtalo de nuevo en unos minutos; si el problema continúa, escríbenos a soporte.');
        }
    }
}
