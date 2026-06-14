<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContextDoctorController extends Controller
{
    /**
     * Alternar el espacio de trabajo del médico entre Consultorio Particular y Clínicas Corporativas.
    */
    public function switchContext(Request $request)
    {
        $request->validate([
            'context_id' => 'nullable|string' // 'particular' o el ID numérico/UUID de la clínica
        ]);

        $contextId = $request->input('context_id');
        $user = auth()->user();

        // 🛡️ BLINDAJE PRODUCCIÓN: Validar existencia del perfil médico antes de operar
        if (!$user || !$user->doctor) {
            abort(403, 'Perfil médico no encontrado o no configurado.');
        }

        // 1. Caso: Regresar al Consultorio Particular e Independiente
        if ($contextId === 'particular' || is_null($contextId)) {
            session(['doctor_context' => [
                'type'  => 'particular',
                'id'    => null,
                'name'  => 'Consultorio Particular',
                'photo' => $user->profile_photo_path // Foto real del médico
            ]]);
        } 
        // 2. Caso: Cambiar al entorno institucional de una Clínica
        else {
            // Validar de forma estricta que el médico pertenezca a esa clínica y esté aprobado
            $belongsToClinic = DB::table('clinic_doctor')
                ->where('doctor_id', $user->doctor->id)
                ->where('clinic_id', $contextId)
                ->where('status', 'approved')
                ->exists();

            if (!$belongsToClinic) {
                abort(403, 'No tienes acceso autorizado a esta clínica corporativa.');
            }

            // Traer el nombre de la clínica y la foto del usuario dueño en una sola consulta
            $clinicData = DB::table('clinics')
                ->join('users', 'clinics.user_id', '=', 'users.id')
                ->where('clinics.id', $contextId)
                ->select('clinics.name', 'users.profile_photo_path')
                ->first();

            // Guardar el contexto dinámico e institucional en la sesión activa
            session(['doctor_context' => [
                'type'  => 'clinic',
                'id'    => $contextId,
                'name'  => $clinicData ? $clinicData->name : 'Clínica Corporativa',
                'photo' => $clinicData ? $clinicData->profile_photo_path : null
            ]]);
        }

        return back()->with('success', 'Entorno cambiado correctamente a: ' . session('doctor_context.name'));
    }
}
