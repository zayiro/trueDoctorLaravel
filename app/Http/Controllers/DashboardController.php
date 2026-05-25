<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Address;
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

        // 1. CONDICIONAL DE SEGURIDAD MULTI-PERFIL
        if ($user->role === 'clinic') {
            $owner = $user->clinic;
            $ownerColumn = 'clinic_id';
        } else {
            $owner = $user->doctor;
            $ownerColumn = 'doctor_id';
        }

        if (!$owner) {
            return redirect()->back()->with('error', 'Perfil comercial no indexado.');
        }

        // 2. INDICADORES EN TIEMPO REAL (KPIs BASE)
        // Volumen de citas hoy
        $citasHoy = Appointment::where($ownerColumn, $owner->id)
            ->whereDate('date', $now->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->count();

        // Citas próximas / pendientes de atención
        $citasProximas = Appointment::where($ownerColumn, $owner->id)
            ->whereDate('date', '>=', $now->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        // Facturación Bruta Mensual (Citas del mes actual completadas o confirmadas)
        $facturacionMes = Appointment::where($ownerColumn, $owner->id)
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('price');

        // Tasa de Ausentismo / Cancelación
        $totalCitasHistoricas = Appointment::where($ownerColumn, $owner->id)->count();
        $totalCanceladas = Appointment::where($ownerColumn, $owner->id)->where('status', 'cancelled')->count();
        
        $tasaCancelacion = $totalCitasHistoricas > 0 
            ? round(($totalCanceladas / $totalCitasHistoricas) * 100, 1) 
            : 0;

        // 3. ESTADÍSTICAS AVANZADAS DE DISTRIBUCIÓN
        // Distribución por Modalidad (Presencial vs Virtual)
        $modalidades = Appointment::where($ownerColumn, $owner->id)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->select('services.type', DB::raw('count(*) as total'))
            ->groupBy('services.type')
            ->pluck('total', 'type')
            ->toArray();
        
        // Distribución por Sedes / Consultorios físicos más rentables
        $sedesTop = Appointment::where('appointments.' . $ownerColumn, $owner->id) // 🔥 CORREGIDO: Especificamos la tabla appointments
            ->join('addresses', 'appointments.address_id', '=', 'addresses.id')
            ->select('addresses.name', 'addresses.id', DB::raw('count(*) as cantidad'), DB::raw('sum(price) as ingresos')) // 🔥 AGREGADO: addresses.id explícito para el group por estándar SQL
            ->groupBy('addresses.name', 'addresses.id')
            ->orderBy('ingresos', 'desc')
            ->take(3)
            ->get();


        // 4. HISTORIAL GRÁFICO (Últimos 5 meses de transacciones)
        $historicoMensual = Appointment::where($ownerColumn, $owner->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->select(
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
            'sedesTop', 'historicoMensual'
        ));
    }
}
