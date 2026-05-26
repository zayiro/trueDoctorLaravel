<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();

        $usuariosPorRol = [];

        // 1. CONDICIONAL DE SEGURIDAD MULTI-PERFIL
        if ($user->role === 'admin') {
            // Al ser administrador global, ve las métricas de todo el SaaS de golpe
            $owner = null;
            $ownerColumn = null;

            // Conteo global de usuarios agrupados por rol para el Administrador
            $usuariosPorRol = User::select('role', DB::raw('count(*) as total'))
                ->groupBy('role')
                ->pluck('total', 'role')
                ->toArray();
        } elseif ($user->role === 'clinic') {
            $owner = $user->clinic;
            $ownerColumn = 'clinic_id';
        } else {
            $owner = $user->doctor;
            $ownerColumn = 'doctor_id';
        }

        // Si no es admin y tampoco tiene un perfil comercial creado, lo mandamos a crear su perfil
        if ($user->role !== 'admin' && !$owner) {
            // Cambia esto a la ruta del onboarding o perfil de tu SaaS si lo prefieres
            return redirect()->route('profile.show')->with('error', 'Perfil comercial no indexado.');
        }

        // 2. INDICADORES EN TIEMPO REAL (KPIs BASE con soporte para Admin)
        // Inicializamos la consulta base de citas
        $queryCitas = Appointment::query();

        // Si no es administrador, filtramos estrictamente por su Tenant / Dueño
        if ($user->role !== 'admin') {
            $queryCitas->where($ownerColumn, $owner->id);
        }

        // Volumen de citas hoy
        $citasHoy = (clone $queryCitas)
            ->whereDate('date', $now->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->count();

        // Citas próximas / pendientes de atención
        $citasProximas = (clone $queryCitas)
            ->whereDate('date', '>=', $now->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        // Facturación Bruta Mensual
        $facturacionMes = (clone $queryCitas)
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('price');

        // Tasa de Ausentismo / Cancelación
        $totalCitasHistoricas = (clone $queryCitas)->count();
        $totalCanceladas = (clone $queryCitas)->where('status', 'cancelled')->count();
        
        $tasaCancelacion = $totalCitasHistoricas > 0 
            ? round(($totalCanceladas / $totalCitasHistoricas) * 100, 1) 
            : 0;

        // 3. ESTADÍSTICAS AVANZADAS DE DISTRIBUCIÓN
        $modalidadesQuery = Appointment::join('services', 'appointments.service_id', '=', 'services.id');
        if ($user->role !== 'admin') {
            $modalidadesQuery->where('appointments.' . $ownerColumn, $owner->id);
        }
        $modalidades = $modalidadesQuery->select('services.type', DB::raw('count(*) as total'))
            ->groupBy('services.type')
            ->pluck('total', 'type')
            ->toArray();
        
        // Distribución por Sedes / Consultorios
        $sedesQuery = Appointment::join('addresses', 'appointments.address_id', '=', 'addresses.id');
        if ($user->role !== 'admin') {
            $sedesQuery->where('appointments.' . $ownerColumn, $owner->id);
        }
        $sedesTop = $sedesQuery->select('addresses.name', 'addresses.id', DB::raw('count(*) as cantidad'), DB::raw('sum(price) as ingresos'))
            ->groupBy('addresses.name', 'addresses.id')
            ->orderBy('ingresos', 'desc')
            ->take(3)
            ->get();

        // 4. HISTORIAL GRÁFICO (Últimos 5 meses)
        $historicoQuery = Appointment::whereIn('status', ['confirmed', 'completed']);
        if ($user->role !== 'admin') {
            $historicoQuery->where($ownerColumn, $owner->id);
        }
        $historicoMensual = $historicoQuery->select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as mes"),
                DB::raw('sum(price) as total'),
                DB::raw('count(*) as conteo')
            )
            ->groupBy('mes')
            ->orderBy('mes', 'desc')
            ->take(5)
            ->get()
            ->reverse();
                
        return view('admin.dashboard', compact(
            'user', 'owner', 'citasHoy', 'citasProximas', 
            'facturacionMes', 'tasaCancelacion', 'modalidades', 
            'sedesTop', 'historicoMensual', 'owner', 'usuariosPorRol'
        ));
    }
}
