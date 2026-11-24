<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SkinAnalysisReady;
use App\Models\SkinAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Listado de análisis de piel para el administrador (reenviar informe y eliminar), igual que los exámenes.
 */
class SkinAnalysisAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = SkinAnalysis::latest();

        if ($request->filled('search')) {
            $query->where('customer_email', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $analyses = $query->paginate(10)->withQueryString();

        return view('administrator.skin-analyses.index', compact('analyses'));
    }

    /** Reenvía el aviso de informe listo (enlace privado al informe). */
    public function resend(SkinAnalysis $skinAnalysis)
    {
        if ($skinAnalysis->status !== SkinAnalysis::STATUS_COMPLETED || empty($skinAnalysis->report)) {
            return back()->with('error', 'Solo se puede reenviar un análisis que ya tiene su informe listo.');
        }

        try {
            Mail::to($skinAnalysis->customer_email)->send(new SkinAnalysisReady($skinAnalysis));

            return back()->with('success', 'El informe de piel fue reenviado a ' . $skinAnalysis->customer_email);
        } catch (\Throwable $e) {
            Log::error("Falla al reenviar informe de piel #{$skinAnalysis->id}: " . $e->getMessage());

            return back()->with('error', 'Ocurrió un problema al intentar enviar el correo electrónico.');
        }
    }

    /** Reintenta un análisis que terminó en error. Las fotos deben seguir guardadas. */
    public function retry(SkinAnalysis $skinAnalysis)
    {
        if ($skinAnalysis->status !== SkinAnalysis::STATUS_ERROR) {
            return back()->with('error', 'Solo se pueden reintentar los análisis con error.');
        }

        try {
            $hayFotos = !empty(Storage::disk('private')->files($skinAnalysis->imageDirectory()));
        } catch (\Throwable $e) {
            $hayFotos = false;
        }

        if (!$hayFotos) {
            return back()->with('error', 'Las fotos de este análisis ya no están guardadas: no se puede reintentar.');
        }

        $actualizado = SkinAnalysis::whereKey($skinAnalysis->id)
            ->where('status', SkinAnalysis::STATUS_ERROR)
            ->update(['status' => SkinAnalysis::STATUS_PROCESSING, 'failure_reason' => null]);

        if ($actualizado) {
            \App\Jobs\SkinAnalysisJob::dispatch($skinAnalysis->fresh());
            Log::info('Análisis de piel reintentado desde el admin', ['analysis_id' => $skinAnalysis->id]);
        }

        return back()->with('success', 'Reintento lanzado. El informe se enviará al paciente al terminar.');
    }
    /** Elimina el análisis y sus fotos. Primero las fotos: si no se pueden borrar, el registro se conserva. */
    public function destroy(SkinAnalysis $skinAnalysis)
    {
        // Solo se bloquea si de verdad está en curso; con más de 30 min sin cambios se considera atascado.
        if ($skinAnalysis->status === SkinAnalysis::STATUS_PROCESSING && $skinAnalysis->updated_at && $skinAnalysis->updated_at->gt(now()->subMinutes(30))) {
            return back()->with('error', 'El análisis se está procesando. Inténtalo cuando termine.');
        }

        try {
            Storage::disk('private')->deleteDirectory($skinAnalysis->imageDirectory());
        } catch (\Throwable $e) {
            Log::error("No se pudieron borrar las fotos del análisis de piel #{$skinAnalysis->id}: " . $e->getMessage());

            return back()->with('error', 'No se pudieron eliminar las fotos. No se eliminó el registro; inténtalo de nuevo.');
        }

        $id = $skinAnalysis->id;
        $skinAnalysis->delete();

        Log::info('Análisis de piel eliminado', ['analysis_id' => $id]);

        return back()->with('success', 'El análisis de piel y sus fotos fueron eliminados.');
    }
}
