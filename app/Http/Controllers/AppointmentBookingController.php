<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AppointmentBookingController extends Controller
{
    /**
     * 🔒 Valida que el usuario tenga permisos para agendar citas.
     * Solo doctores y clínicas pueden acceder a este módulo.
     */
    public function __construct()
    {
        $user = Auth::user();
        
        if (!$user || !in_array($user->role, ['doctor', 'clinic'], true)) {
            abort(403, 'No tienes permisos para acceder al módulo de agendamiento.');
        }
    }

    /**
     * Muestra el formulario unificado de agendamiento (SPA).
     */
    public function create(): \Illuminate\Contracts\View\View
    {
        return view('partner.booking.create');
    }

    /**
     * Actualiza los campos del flujo de agendamiento en la sesión.
     * 
     * 🔒 CORRECCIÓN: Valida que los grupos y claves sean permitidos para evitar inyección de sesión.
     */
    public function updateSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string', 'in:root,patient,appointment'],
            'key'   => ['required', 'string', 'max:50'],
            'value' => ['nullable'],
        ]);

        $group = $validated['group'];
        $key = $validated['key'];
        $value = $validated['value'];

        // 🔒 BLINDAJE: Solo permitir claves específicas para evitar sobrescritura de datos críticos
        $allowedKeys = [
            'root' => ['doctor_id', 'address_id', 'service_id', 'date', 'start_time'],
            'patient' => ['all', 'id', 'name', 'email', 'phone', 'identification'],
            'appointment' => ['notes', 'reason', 'duration'],
        ];

        if ($group === 'patient' && $key === 'all') {
            // Permitir reemplazo completo del paciente solo si es un array válido
            if (!is_array($value)) {
                return response()->json(['status' => 'error', 'message' => 'El valor debe ser un array.'], 400);
            }
            session(['appointment_flow.patient' => $value]);
        } elseif (in_array($key, $allowedKeys[$group] ?? [], true)) {
            session(["appointment_flow.{$group}.{$key}" => $value]);
        } else {
            Log::warning('Intento de inyección de sesión en AppointmentBookingController', [
                'user_id' => Auth::id(),
                'group' => $group,
                'key' => $key,
            ]);
            return response()->json(['status' => 'error', 'message' => 'Clave no permitida.'], 403);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Limpia los datos temporales del flujo en la sesión al finalizar.
     */
    public function clearSession(): JsonResponse
    {
        session()->forget('appointment_flow');
        return response()->json(['status' => 'success']);
    }

    /**
     * Busca un paciente en el sistema por su número de identificación encriptado.
     * 
     * 🔒 CORRECCIÓN: Manejo de errores y validación de paciente activo.
     */
    public function searchPatient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identification' => ['required', 'string', 'max:20'],
        ]);

        $identification = trim($validated['identification']);

        if (empty($identification)) {
            return response()->json([
                'found' => false, 
                'message' => 'Identificación vacía.'
            ], 400);
        }

        try {
            //  VALIDACIÓN: Verificar que el método existe en el modelo
            if (!method_exists(Patient::class, 'generateBlindIndex')) {
                Log::error('Método generateBlindIndex no existe en el modelo Patient');
                return response()->json([
                    'found' => false, 
                    'message' => 'Error de configuración del sistema.'
                ], 500);
            }

            // 1. Generamos el mismo hash para la identificación buscada
            $blindIndex = Patient::generateBlindIndex($identification);

            // 2. Buscamos de forma directa, indexada e instantánea en la BD
            $patient = Patient::with('user')
                ->where('identification_blind_index', $blindIndex)
                ->where('active', true)
                ->first();

            if ($patient) {
                return response()->json([
                    'found' => true,
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $patient->user->name ?? 'Paciente sin nombre',
                        'email' => $patient->user->email ?? '',
                        'phone' => $patient->phone ?? '',
                        'identification' => $identification,
                    ]
                ]);
            }

            // 🔒 CORRECCIÓN: Verificar si existe pero está inactivo
            $inactivePatient = Patient::where('identification_blind_index', $blindIndex)
                ->where('active', false)
                ->exists();

            if ($inactivePatient) {
                return response()->json([
                    'found' => false,
                    'message' => 'El paciente existe pero su cuenta está inactiva. Contacta al administrador.'
                ]);
            }

            return response()->json(['found' => false]);
        } catch (\Exception $e) {
            Log::error('Error al buscar paciente por identificación', [
                'error' => $e->getMessage(),
                'identification' => $identification,
            ]);

            return response()->json([
                'found' => false,
                'message' => 'Ocurrió un error al buscar el paciente. Intenta nuevamente.'
            ], 500);
        }
    }
}