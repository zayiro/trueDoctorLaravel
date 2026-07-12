<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Models\SearchLog;

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
        $popularSpecialties = []; 
        $popularCities = [];

        // 1. CONDICIONAL DE SEGURIDAD MULTI-PERFIL
        if ($user->role === 'admin') {
            $owner = null;
            $ownerColumn = null;

            $popularSpecialties = SearchLog::topSpecialties(15);
            $popularCities = SearchLog::topCities(15);

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
            $owner = $user->patient;
            $ownerColumn = 'patient_id';
        }
        
        if ($user->role !== 'admin' && !$owner) {
            return redirect()->route('profile.show')->with('error', 'Perfil no encontrado.');
        }

        // =========================================================================
        // FLUJO ESPECÍFICO PARA EL ROL DE PACIENTE (PATIENT)
        // =========================================================================
        if ($user->role === 'patient') {
            
            $upcomingAppointments = Appointment::where('patient_id', $owner->id)
                ->whereDate('date', '>=', $now->toDateString())
                ->whereIn('status', ['confirmed', 'pending'])
                ->with('doctor.user')
                ->orderBy('date', 'asc')
                ->take(5)
                ->get();

            $pastAppointments = Appointment::where('patient_id', $owner->id)
                ->whereIn('status', ['completed'])
                ->orderBy('date', 'desc')
                ->take(5)
                ->get();

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

        if ($user->role !== 'admin') {
            $appointmentsQuery->where($ownerColumn, $owner->id);
        }

        // ========== CONSULTAS DE HOY - SEPARADAS POR MODALIDAD ==========
        $appointmentsTodayQuery = (clone $appointmentsQuery)
            ->whereDate('date', $now->toDateString())
            ->whereNotIn('status', ['cancelled']);

        $appointmentsToday = $appointmentsTodayQuery->count();

        $appointmentsTodayByModality = (clone $appointmentsTodayQuery)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->select('services.type', DB::raw('count(*) as total'))
            ->groupBy('services.type')
            ->pluck('total', 'type')
            ->toArray();

        $appointmentsTodayVirtual = $appointmentsTodayByModality['virtual'] ?? 0;
        $appointmentsTodayPresencial = $appointmentsTodayByModality['presencial'] ?? 0;

        // ========== CITAS PRÓXIMAS ==========
        $upcomingAppointmentsCount = (clone $appointmentsQuery)
            ->whereDate('date', '>=', $now->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        // ========== FACTURACIÓN MENSUAL ==========
        $monthlyRevenue = (clone $appointmentsQuery)
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('price');

        // ========== TASA DE CANCELACIÓN ==========
        $totalHistoricalAppointments = (clone $appointmentsQuery)->count();
        $totalCancelledAppointments = (clone $appointmentsQuery)->where('status', 'cancelled')->count();
        
        $cancellationRate = $totalHistoricalAppointments > 0 
            ? round(($totalCancelledAppointments / $totalHistoricalAppointments) * 100, 1) 
            : 0;

        // ========== DISTRIBUCIÓN POR MODALIDADES ==========
        $modalitiesQuery = Appointment::join('services', 'appointments.service_id', '=', 'services.id');
        if ($user->role !== 'admin') {
            $modalitiesQuery->where('appointments.' . $ownerColumn, $owner->id);
        }

        $modalities = $modalitiesQuery->select('services.type', DB::raw('count(*) as total'))
            ->groupBy('services.type')
            ->pluck('total', 'type')
            ->toArray();
        
        // ========== TOP UBICACIONES CLÍNICAS ==========
        $locationQuery = Appointment::join('addresses', 'appointments.address_id', '=', 'addresses.id');
        if ($user->role !== 'admin') {
            $locationQuery->where('appointments.' . $ownerColumn, $owner->id);
        }

        $topClinicalLocations = $locationQuery->select('addresses.name', 'addresses.id', DB::raw('count(*) as cantidad'), DB::raw('sum(price) as ingresos'))
            ->groupBy('addresses.name', 'addresses.id')
            ->orderBy('ingresos', 'desc')
            ->take(3)
            ->get();

        // ========== HISTORIAL MENSUAL SEPARADO POR VIRTUAL/PRESENCIAL ==========
        $historicalQuery = Appointment::whereIn('status', ['confirmed', 'completed']);
        if ($user->role !== 'admin') {
            $historicalQuery->where($ownerColumn, $owner->id);
        }

        $monthlyHistorical = $historicalQuery
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->select(
                DB::raw("DATE_FORMAT(appointments.date, '%Y-%m') as mes"),
                'services.type',
                DB::raw('sum(appointments.price) as total'),
                DB::raw('count(*) as conteo')
            )
            ->groupBy('mes', 'services.type')
            ->orderBy('mes', 'asc')
            ->limit(10)
            ->get();

        // ========== PREPARAR DATOS PARA GRÁFICO ==========
        $chartLabels = [];
        $chartData = [];
        $chartDataVirtual = [];
        $chartDataPresencial = [];

        $monthNames = [
            '01'=>'Ene', '02'=>'Feb', '03'=>'Mar', '04'=>'Abr', '05'=>'May', '06'=>'Jun',
            '07'=>'Jul', '08'=>'Ago', '09'=>'Sep', '10'=>'Oct', '11'=>'Nov', '12'=>'Dic'
        ];

        // Agrupar datos por mes
        $monthlyDataMap = [];
        foreach ($monthlyHistorical as $data) {
            $parts = explode('-', $data->mes);
            $label = isset($parts[1]) ? ($monthNames[$parts[1]] . ' ' . substr($parts[0], 2)) : $data->mes;
            
            if (!isset($monthlyDataMap[$label])) {
                $monthlyDataMap[$label] = ['virtual' => 0, 'presencial' => 0];
            }
            
            $monthlyDataMap[$label][$data->type] = (float) $data->total;
        }

        // Llenar arrays para Chart.js
        foreach ($monthlyDataMap as $label => $values) {
            $chartLabels[] = $label;
            $virtualTotal = $values['virtual'];
            $presencialTotal = $values['presencial'];
            $chartData[] = $virtualTotal + $presencialTotal; // Total combinado para barras
            $chartDataVirtual[] = $virtualTotal;
            $chartDataPresencial[] = $presencialTotal;
        }

        // ========== DISTRIBUCIÓN FINANCIERA POR SEDES ==========
        $locationLabels = [];
        $locationRevenueData = [];

        foreach ($topClinicalLocations as $location) {
            $locationLabels[] = Str::limit($location->name, 20, '...');
            $locationRevenueData[] = (float) $location->ingresos;
        }
               
        return view('admin.dashboard', compact(
            'user', 'owner', 'appointmentsToday', 'appointmentsTodayVirtual', 'appointmentsTodayPresencial',
            'upcomingAppointmentsCount', 'monthlyRevenue', 'cancellationRate', 'modalities', 
            'topClinicalLocations', 'monthlyHistorical', 'usersByRole',
            'chartLabels', 'chartData', 'chartDataVirtual', 'chartDataPresencial', 'locationLabels',
            'locationRevenueData', 'popularSpecialties', 'popularCities'
        ));
    }
}