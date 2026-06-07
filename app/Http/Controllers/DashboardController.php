<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {        
        $user = Auth::user()->load('doctorSettings');        
        $now = Carbon::now();

        $usersByRole = [];

        // 1. CONDICIONAL DE SEGURIDAD MULTI-PERFIL
        if ($user->role === 'admin') {
            // Al ser administrador global, ve las métricas de todo el SaaS de golpe
            $owner = null;
            $ownerColumn = null;

            // Conteo global de usuarios agrupados por rol para el Administrador
            $usersByRole = User::select('role', DB::raw('count(*) as total'))
                ->groupBy('role')
                ->pluck('total', 'role')
                ->toArray();
        } elseif ($user->role === 'clinic') {
            $owner = $user->clinic;
            $ownerColumn = 'clinic_id';
        } elseif ($user->role === 'doctor') {
            $owner = $user->doctor;
            $ownerColumn = 'doctor_id';
        } else {
            // El usuario tiene el rol de paciente (patient)
            $owner = $user->patient;
            $ownerColumn = 'patient_id';
        }
        
        // Si no es admin y tampoco tiene un perfil creado, lo mandamos a completar sus datos
        if ($user->role !== 'admin' && !$owner) {
            return redirect()->route('profile.show')->with('error', 'Perfil no encontrado.');
        }

        // =========================================================================
        // FLUJO ESPECÍFICO PARA EL ROL DE PACIENTE (PATIENT)
        // =========================================================================
        if ($user->role === 'patient') {
            
            // Obtiene las próximas 5 citas pendientes o confirmadas del paciente
            $upcomingAppointments = Appointment::where('patient_id', $owner->id)
                ->whereDate('date', '>=', $now->toDateString())
                ->whereIn('status', ['confirmed', 'pending'])
                ->with('doctor.user') // Carga la relación para obtener el nombre del médico
                ->orderBy('date', 'asc')
                ->take(5)
                ->get();

            // Obtiene las últimas 5 citas completadas / asistidas históricas
            $pastAppointments = Appointment::where('patient_id', $owner->id)
                ->whereIn('status', ['completed'])
                ->orderBy('date', 'desc')
                ->take(5)
                ->get();

            // Estructura los indicadores médicos extraídos del modelo Patient
            $healthMetrics = [
                'bmi' => $owner->imc,
                'bmi_status' => $owner->imc_status,
                'age' => $owner->age,
                'blood_type' => $owner->blood_type ?? 'No registrada'
            ];            

            return view('admin.dashboard', compact(
                'user', 'owner', 'upcomingAppointments', 'pastAppointments', 'healthMetrics'
            ));
        }

        // =========================================================================
        // FLUJO CORPORATIVO (ADMINISTRADORES, CLÍNICAS Y MÉDICOS)
        // =========================================================================
        $appointmentsQuery = Appointment::query();

        // Si no es administrador global, filtra estrictamente por su Tenant / Dueño
        if ($user->role !== 'admin') {
            $appointmentsQuery->where($ownerColumn, $owner->id);
        }

        // Volumen de citas para el día de hoy
        $appointmentsToday = (clone $appointmentsQuery)
            ->whereDate('date', $now->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->count();

        // Cantidad de citas próximas o pendientes de atención médica
        $upcomingAppointmentsCount = (clone $appointmentsQuery)
            ->whereDate('date', '>=', $now->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        // Facturación / Ingresos brutos mensuales de la clínica o médico
        $monthlyRevenue = (clone $appointmentsQuery)
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('price');

        // Cálculo porcentual de la tasa de ausentismo o cancelaciones globales
        $totalHistoricalAppointments = (clone $appointmentsQuery)->count();
        $totalCancelledAppointments = (clone $appointmentsQuery)->where('status', 'cancelled')->count();
        
        $cancellationRate = $totalHistoricalAppointments > 0 
            ? round(($totalCancelledAppointments / $totalHistoricalAppointments) * 100, 1) 
            : 0;

        // Distribución avanzada por modalidades de servicio
        $modalitiesQuery = Appointment::join('services', 'appointments.service_id', '=', 'services.id');
        if ($user->role !== 'admin') {
            $modalitiesQuery->where('appointments.' . $ownerColumn, $owner->id);
        }
        $modalities = $modalitiesQuery->select('services.type', DB::raw('count(*) as total'))
            ->groupBy('services.type')
            ->pluck('total', 'type')
            ->toArray();
        
        // Distribución física por sedes o consultorios principales
        $locationQuery = Appointment::join('addresses', 'appointments.address_id', '=', 'addresses.id');
        if ($user->role !== 'admin') {
            $locationQuery->where('appointments.' . $ownerColumn, $owner->id);
        }

        $topClinicalLocations = $locationQuery->select('addresses.name', 'addresses.id', DB::raw('count(*) as cantidad'), DB::raw('sum(price) as ingresos'))
            ->groupBy('addresses.name', 'addresses.id')
            ->orderBy('ingresos', 'desc')
            ->take(3)
            ->get();

        // Historial gráfico financiero consolidado de los últimos 5 meses
        $historicalQuery = Appointment::whereIn('status', ['confirmed', 'completed']);
        if ($user->role !== 'admin') {
            $historicalQuery->where($ownerColumn, $owner->id);
        }

        $monthlyHistorical = $historicalQuery->select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as mes"),
                DB::raw('sum(price) as total'),
                DB::raw('count(*) as conteo')
            )
            ->groupBy('mes')
            ->orderBy('mes', 'desc')
            ->take(5)
            ->get()
            ->reverse();

        // Formateamos los últimos 5 meses para el motor gráfico de Chart.js
        $chartLabels = [];
        $chartData = [];
                            
        // Mapeo en español para los meses
        $monthNames = [
            '01'=>'Ene', '02'=>'Feb', '03'=>'Mar', '04'=>'Abr', '05'=>'May', '06'=>'Jun',
            '07'=>'Jul', '08'=>'Ago', '09'=>'Sep', '10'=>'Oct', '11'=>'Nov', '12'=>'Dic'
        ];

        foreach ($monthlyHistorical as $data) {
            // Separa '2026-05' en año y mes
            $parts = explode('-', $data->mes);
            $label = isset($parts[1]) ? ($monthNames[$parts[1]] . ' ' . substr($parts[0], 2)) : $data->mes;
            
            $chartLabels[] = $label;
            $chartData[] = (float) $data->total;
        }

        // 📊 GRÁFICO DE DONA: Distribución financiera consolidada por sedes
        $locationLabels = [];
        $locationRevenueData = [];

        foreach ($topClinicalLocations as $location) {
            // Acortamos el nombre de la sede de forma elegante si excede los 20 caracteres
            $locationLabels[] = Str::limit($location->name, 20, '...');
            $locationRevenueData[] = (float) $location->ingresos;
        }
        
        return view('admin.dashboard', compact(
            'user', 'owner', 'appointmentsToday', 'upcomingAppointmentsCount', 
            'monthlyRevenue', 'cancellationRate', 'modalities', 
            'topClinicalLocations', 'monthlyHistorical', 'usersByRole',
            'chartLabels', 'chartData', 'locationLabels',
            'locationRevenueData'
        ));
    }
}
