<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExamAnalysis;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExamAnalysisReady;
use Illuminate\Support\Facades\URL; 
use Illuminate\Support\Str; 
use App\Mail\ExamPaymentPendingAlert; 
use Spatie\PdfToImage\Pdf;

class MedicalExamController extends Controller
{
    public function index()
    {
        return view('medical-exams.index');
    }

    // Paso 1: Recibir el archivo y contexto, y redirigir a caja
    public function store(Request $request)
    {
        $request->validate([
            'exam_file'     => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'customer_email' => 'required|email|max:255',
            'reason_type'   => 'required|string',
            'reason_custom' => 'nullable|string|max:500',
        ]);

        // Guardar el archivo de forma segura
        $path = $request->file('exam_file')->store('medical-exams', 'private');

        $analysis = ExamAnalysis::create([
            'user_id'       => auth()->id(),
            'customer_email' => trim(strtolower($request->customer_email)),
            'file_path'     => $path,
            'reason_type'   => $request->reason_type,
            'reason_custom' => $request->reason_custom,
            'price'         => 18500, // Cambia al precio real de tu SaaS
            'payment_status'=> 'pending'
        ]);

        // Crear una URL firmada y segura que expire en 24 horas
        $recoveryUrl = URL::temporarySignedRoute(
            'exams.checkout', 
            now()->addHours(24), 
            ['id' => $analysis->id]
        );

        // Enviar correo inmediato con el enlace de rescate por si se le cierra la pestaña
        Mail::to($analysis->customer_email)->send(new ExamPaymentPendingAlert($recoveryUrl, $analysis));

        return redirect()->route('exams.checkout', $analysis->id);
    }

    public function checkout($id)
    {
        $analysis = ExamAnalysis::findOrFail($id);
        return view('medical-exams.checkout', compact('analysis'));
    }

    // Paso 2: Procesar el pago y ejecutar la IA
    public function processPayment(Request $request, $id)
    {
        $analysis = ExamAnalysis::findOrFail($id);

        // Aquí integrarías tu pasarela (Stripe, MercadoPago, etc.)
        // Simulamos un pago exitoso:
        $paymentSuccess = true; 

        if (!$paymentSuccess) {
            return back()->with('error', 'El pago no pudo ser procesado.');
        }

        // Actualizar estado del pago
        $analysis->update([
            'payment_status' => 'paid',
            'payment_id'     => 'PAY-' . strtoupper(Str::random(10))
        ]);

        // Ejecutar el análisis de Inteligencia Artificial
        $this->analyzeWithAI($analysis);

        return redirect()->route('exams.result', $analysis->id)->with('success', '¡Pago exitoso! Tu reporte ha sido generado.');
    }

    private function analyzeWithAI(ExamAnalysis $analysis)
    {
        $filePath = Storage::disk('private')->path($analysis->file_path);
        $mimeType = mime_content_type($filePath);
        
        $finalBase64 = '';
        $finalMime = '';

        // Si es PDF, lo convertimos a imagen temporalmente
        if ($mimeType === 'application/pdf') {
            $pdf = new Pdf($filePath);
            $tempImagePath = storage_path('app/private/temp_' . time() . '.jpg');
            $pdf->saveImage($tempImagePath);
            
            $finalBase64 = base64_encode(file_get_contents($tempImagePath));
            $finalMime = 'image/jpeg';
            
            if (file_exists($tempImagePath)) {
                unlink($tempImagePath);
            }
        } else {
            $finalBase64 = base64_encode(file_get_contents($filePath));
            $finalMime = $mimeType;
        }

        $dataUri = "data:{$finalMime};base64,{$finalBase64}";
        $contextoMotivo = "El paciente se tomó este examen por: {$analysis->reason_type}. Detalles adicionales: {$analysis->reason_custom}";

        // Llamada oficial a OpenAI
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',            
            'messages' => [
                [
                    'role' => 'system', 
                    'content' => "Eres un asistente médico experto de un SAAS de salud. Tu tarea es extraer la información del examen y obligatoriamente clasificarlo en una especialidad médica."
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text', 
                            'text' => "Analiza este examen médico correlacionándolo con el motivo del paciente ({$analysis->reason_type}). Extrae los biomarcadores, conclusiones claras y próximos pasos. REGLA CRÍTICA: En el campo 'especialidad_slug' debes colocar OBLIGATORIAMENTE uno de estos términos en minúsculas y sin acentos según corresponda: 'medicina-general', 'neurologia', 'cardiologia', 'ginecologia', 'endocrinologia', 'pediatria', 'urologia', 'dermatologia'. Si tienes dudas, usa 'medicina-general'."
                        ],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUri]]
                    ]
                ]
            ],

            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'analisis_clinico_pago',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'nombre_examen' => ['type' => 'string'],
                            'especialidad_slug' => [
                                'type' => 'string',
                                'description' => 'El slug en minúsculas de la especialidad correspondiente de la lista entregada.'
                            ],
                            'hallazgos_clave' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'parametro' => ['type' => 'string'],
                                        'valor_detectado' => ['type' => 'string'],
                                        'estado' => ['type' => 'string', 'enum' => ['Normal', 'Elevado', 'Bajo', 'Crítico']],
                                    ],
                                    'required' => ['parametro', 'valor_detectado', 'estado'],
                                    'additionalProperties' => false
                                ]
                            ],
                            'conclusion_paciente' => ['type' => 'string'],
                            'recomendaciones' => ['type' => 'string']
                        ],                        
                        'required' => ['nombre_examen', 'especialidad_slug', 'hallazgos_clave', 'conclusion_paciente', 'recomendaciones'],
                        'additionalProperties' => false
                    ]
                ]
            ]
        ]);

        // Guardar el resultado en la base de datos de forma asociativa correcta
        $analysis->update([
            'ai_result' => json_decode($response['choices'][0]['message']['content'], true)
        ]);

        // Enviar correo electrónico de respaldo
        Mail::to($analysis->customer_email)->send(new ExamAnalysisReady($analysis));
    }

    public function showResult($id)
    {
        $analysis = ExamAnalysis::findOrFail($id);

        if ($analysis->payment_status !== 'paid') {
            return redirect()->route('exams.checkout', $analysis->id)->with('error', 'Debes pagar para ver este resultado.');
        }

        $analysisResult = $analysis->ai_result;
        return view('medical-exams.result', compact('analysis', 'analysisResult'));
    }
}
