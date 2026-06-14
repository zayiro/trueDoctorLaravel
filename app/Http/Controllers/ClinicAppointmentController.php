<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Appointment;

class ClinicAppointmentController extends Controller
{
    /**
     * Despliega la gran lista centralizada de citas de la clínica.
     * Filtra dinámicamente por médico, estado, fecha o búsqueda global de paciente.
     */
    public function index(Request $request)
    {
        $clinicUser = auth()->user();
        
        // 1. Validar que el usuario sea una clínica y tenga su perfil configurado
        if ($clinicUser->role !== 'clinic' || !$clinicUser->clinic) {
            abort(403, 'Acceso restringido únicamente para clínicas autorizadas.');
        }

        $clinicId = $clinicUser->clinic->id;

        // 2. Obtener lista de médicos del staff aprobado para los filtros de la vista
        $staffDoctors = DB::table('clinic_doctor')
            ->join('doctors', 'clinic_doctor.doctor_id', '=', 'doctors.id')
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->where('clinic_doctor.clinic_id', $clinicId)
            ->where('clinic_doctor.status', 'approved')
            ->select('doctors.id', 'users.name')
            ->get();

        // 3. Obtener sedes institucionales para los filtros
        $clinicAddresses = DB::table('addresses')
            ->where('clinic_id', $clinicId)
            ->where('status', true)
            ->select('id', 'name')
            ->get();

        // 4. Query transaccional base filtrando estrictamente por el tenant de la clínica
        $query = Appointment::where('clinic_id', $clinicId)
            ->with(['patient', 'doctor.user', 'address', 'service']);

        // 5. Aplicar Filtros Dinámicos del Panel Comercial
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->input('doctor_id'));
        }

        if ($request->filled('address_id')) {
            $query->where('address_id', $request->input('address_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('patient', function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // 6. Paginación segura para producción ordenando por la más cercana
        $appointments = $query->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('partner.clinic.appointments.index', [
            'appointments'    => $appointments,
            'staffDoctors'    => $staffDoctors,
            'clinicAddresses' => $clinicAddresses,
        ]);
    }
}
