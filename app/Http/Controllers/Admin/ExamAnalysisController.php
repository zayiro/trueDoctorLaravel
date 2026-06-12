<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExamAnalysisReady;
use Illuminate\Support\Facades\Log;

class ExamAnalysisController extends Controller
{
    /**
     * Muestra el listado de exámenes con filtros básicos.
     */
    public function index(Request $request)
    {
        $query = ExamAnalysis::latest();

        if ($request->filled('search')) {
            $query->where('customer_email', 'like', '%' . $request->search . '%');
        }

        $exams = $query->paginate(10)->withQueryString();

        return view('administrator.exams.index', compact('exams'));
    }

    /**
     * Reenvía el correo electrónico con el informe del examen.
     */
    public function resend(ExamAnalysis $exam)
    {
        // Validar que el examen tenga un resultado de la IA disponible
        if (!$exam->ai_result) {
            return back()->with('error', 'No se puede reenviar un examen que no ha sido procesado por la IA.');
        }

        try {
            // Enviar el correo electrónico al paciente
            Mail::to($exam->customer_email)->send(new ExamAnalysisReady($exam));

            return back()->with('success', 'El examen médico ha sido reenviado con éxito a ' . $exam->customer_email);
            
        } catch (\Exception $e) {
            // Registrar el error interno en los logs de Laravel
            Log::error("Falla al reenviar correo de examen #{$exam->id}: " . $e->getMessage());

            return back()->with('error', 'Ocurrió un problema al intentar enviar el correo electrónico.');
        }
    }

    public function toggleStatus(ExamAnalysis $examAnalysis)
    {        
        $examAnalysis->update([
            'payment_status' => 'paid'
        ]);

        return back()->with('success', 'El examen médico se ha pagado correctamente');
    }
}
