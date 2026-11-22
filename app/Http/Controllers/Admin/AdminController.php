<?php

namespace App\Http\Controllers\Admin;

use App\Models\Doctor;
use App\Models\IndexedSymptom;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\DoctorPayout;

class AdminController extends Controller
{
    public function __construct(){}

    public function listIndexedSymptoms(Request $request)
    {
        // 1. Consulta optimizada ordenando por especialidad y luego por los más nuevos
        $symptoms = IndexedSymptom::select(['id', 'original_query', 'slug', 'specialty_suggested', 'created_at'])
            ->orderBy('specialty_suggested', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(15) // Paginación de 15 elementos por página
            ->withQueryString(); // Mantiene filtros si decides agregarlos luego

        // 2. Contador total para medir el avance del SEO Programático
        $totalSymptoms = IndexedSymptom::count();

        // 3. Retornamos la vista enviando los datos
        return view('admin.symptoms.index', compact('symptoms', 'totalSymptoms'));
    }

    public function clearCache()
    {        
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('permission:cache-reset');
        Artisan::call('optimize:clear');
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cache eliminada correctamente.'
        ]);
    }

    public function refunds(Request $request)
    {
        $query = \App\Models\PaymentRefund::with([
            'payment',
            'processor',
            'appointment.patient.user',
            'appointment.doctor.user',
            'appointment.clinic',
        ])->orderByRaw("status = 'pending' desc")->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $refunds      = $query->paginate(15)->withQueryString();
        $totalPending = \App\Models\PaymentRefund::where('status', 'pending')->sum('amount_in_cents') / 100;

        return view('administrator.refunds.index', compact('refunds', 'totalPending'));
    }

    public function markRefundDone(Request $request, \App\Models\PaymentRefund $refund)
    {
        $data = $request->validate([
            'provider_reference' => 'required|string|max:255',
            'admin_note'         => 'nullable|string|max:500',
        ]);

        try {
            app(\App\Services\Payments\RefundService::class)->complete(
                $refund,
                $request->user(),
                $data['provider_reference'],
                $data['admin_note'] ?? null
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reembolso registrado. Se avisó al paciente por correo.');
    }

}
