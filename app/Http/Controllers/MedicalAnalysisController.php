<?php

namespace App\Http\Controllers;

use App\Models\MedicalAnalysis;
use App\Models\Setting;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

use Illuminate\Support\Facades\Http;
use App\Services\AI\AIVisionManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\PdfToImage\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExamAnalysisReady;
use App\Mail\ExamPaymentPendingAlert; 
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\PdfToImage\Enums\OutputFormat;

class MedicalAnalysisController extends Controller
{
    public function __construct(){}

    public function index()
    {
        return view('medical-analysis.index');
    }

    /**
     * Renders the upload view.
     */
    public function showUploadForm()
    {
        return view('medical-analysis.upload');
    }

    /**
     * Processes multiple medical PDFs and anonymizes them.
     * EL MÉTODO PÚBLICO (Recibe el AJAX del upload)
     */
    public function processDocuments(Request $request)
    {
        // 1. Validaciones estrictas del formulario
        $request->validate([
            'pdf_files' => 'required|array|min:1|max:5',
            'pdf_files.*' => 'required|mimes:pdf|max:10000',
            'customer_email' => 'required|email',
            'reason_type' => 'required|string',
            'reason_custom' => 'nullable|string'
        ]);

        // Guardar físicamente los archivos en el almacenamiento privado (OBLIGATORIO para el método de visión)
        $storedPaths = [];
        foreach ($request->file('pdf_files') as $file) {
            $storedPaths[] = $file->store('medical-exams', 'private');
        }

        $price = Setting::get('medical_analysis_price', 19000); 

        // CREAR EL REGISTRO (Aquí queda tu respaldo seguro en la DB con estado 'pending')
        $analysis = MedicalAnalysis::create([
            'file_paths'     => json_encode($storedPaths),
            'customer_email' => strtolower(trim($request->input('customer_email'))),
            'reason_type'    => $request->input('reason_type'),
            'reason_custom'  => trim($request->input('reason_custom')),
            'price'          => $price, 
            'status'         => 'pending',
            'payment_status' => 'pending'
        ]);

        // Enviar correo inmediato con el enlace de rescate por si se le cierra la pestaña
        $recoveryUrl = route('medical-analysis.show', $analysis->access_token);
        
        //Envio del correo con el token, para ingresar de nuevo si pasa algun error de conexion o no se completo el resultado
        Mail::to($analysis->customer_email)->send(new ExamPaymentPendingAlert($recoveryUrl, $analysis));

        // Intentar la consulta inmediata a la IA pasándole el modelo completo
        $this->analyzeWithAI($analysis);

        // Responder al JavaScript para la redirección fluida en el navegador
        return response()->json([
            'status' => 'success',
            'redirect_url' => $recoveryUrl
        ]);
    }
    
    /**
     * Analiza los PDFs/imágenes del análisis médico usando el proveedor de IA configurado.
     *
     * @param  string|null  $provider  Override manual: 'openai' | 'claude' | null (usa default de config)
     * @param  bool  $withFallback     Si true, intenta con el otro proveedor si el primario falla
     */
    public function analyzeWithAI(MedicalAnalysis $analysis, ?string $provider = null, bool $withFallback = false)
    {
        $reasons = [
            'rutina'   => 'Control de rutina anual o chequeo preventivo.',
            'control'  => 'Seguimiento continuo de una patología médica existente.',
            'sintomas' => 'Evaluación motivada por sintomatología reciente del paciente.',
            'otros'    => 'Motivos complementarios.'
        ];

        $motivoClinico = $reasons[$analysis->reason_type] ?? $analysis->reason_type;
        $detallesAdicionales = $analysis->reason_custom ?? 'No se proporcionaron detalles adicionales.';
        $contextoPaciente = "CONTEXTO DEL PACIENTE:\n- Motivo: {$motivoClinico}\n- Detalles: {$detallesAdicionales}";

        $userText = "{$contextoPaciente}\n\nAnaliza visualmente las imágenes médicas y dime como un médico experto lo que significan los valores, en un lenguaje natural de paciente. Extrae los biomarcadores transversales, conclusiones claras y próximos pasos comunes. REGLA CRÍTICA: En el campo 'especialidad_slug' debes colocar OBLIGATORIAMENTE uno de estos términos en minúsculas y sin acentos según corresponda: 'medicina-general', 'neurologia', 'cardiologia', 'ginecologia', 'endocrinologia', 'pediatria', 'urologia', 'dermatologia'. Si tienes dudas o el caso es mixto, usa 'medicina-general'.";

        $systemPrompt = "Actúa como un médico especialista clínico con excelente comunicación humana y empatía.
        Responde SIEMPRE en español, independientemente del idioma en que estén escritos los resultados.

        Tu tarea principal es: extraer la información consolidada de los exámenes provistos
        (pueden ser múltiples documentos o imágenes), correlacionar los biomarcadores entre sí,
        clasificar obligatoriamente el caso en una especialidad médica, y explicarle los resultados
        al paciente en lenguaje natural, accesible y sin tecnicismos innecesarios,
        manteniendo siempre la precisión médica.

        IMPORTANTE ANTES DE ANALIZAR:
        - Si alguna imagen no es legible, está incompleta o no contiene resultados de laboratorio,
        indícaselo amablemente al paciente sin inventar datos.
        - Nunca asumas ni inventes valores que no estén claramente visibles en los documentos provistos.
        - Si se proveen múltiples documentos, consolida toda la información antes de responder;
        no analices cada examen de forma aislada.

        Por favor, estructura tu respuesta siguiendo estrictamente estas secciones:

        1. INTRODUCCIÓN Y CLASIFICACIÓN ESPECIALIDAD:
        Comienza indicando a qué especialidad médica corresponde el caso
        (ej. Nefrología, Endocrinología, Cardiología, Medicina General, etc.) y por qué.
        Luego ofrece una conclusión general muy clara: qué está bien y qué necesita
        atención urgente o seguimiento.

        2. SECCIONES CLÍNICAS (Usa encabezados de Markdown):
        Divide los resultados por sistemas o bloques lógicos (ej. Función Renal, Perfil de Lípidos,
        Hemograma, Metabolismo de Glucosa, etc.) para que el paciente entienda el contexto
        de cada prueba. Si hay correlaciones relevantes entre biomarcadores de distintos bloques,
        menciónalo explícitamente (ej. 'La glucosa alta junto con los triglicéridos elevados
        sugiere un patrón metabólico que vale la pena revisar').

        3. EXPLICACIÓN DE VALORES:
        Para cada parámetro importante analizado:
        - Menciona el nombre de la prueba y el valor exacto del paciente.
        - Explica de forma muy sencilla qué mide ese parámetro (usa analogías si es útil,
            como 'el filtro del riñón' o 'el camión de basura del colesterol').
        - Indica explícitamente si el valor es Normal, Alto o Bajo, usando rangos de referencia
            internacionales estándar (OMS o laboratorios de referencia).
            Si el rango varía por sexo o edad, menciónalo.

        4. PLAN DE ACCIÓN Y RECOMENDACIONES:
        Agrupa los pasos prácticos que debe tomar el paciente: alimentación, ejercicio
        y consulta médica presencial (especificando la especialidad recomendada si aplica).
        Incluye siempre una nota pidiendo al paciente verificar su reporte impreso
        para confirmar los datos.

        5. PREGUNTAS DE SEGUIMIENTO:
        Termina con 2 o 3 preguntas clave sobre su historial clínico (antecedentes familiares,
        enfermedades crónicas, medicamentos actuales) para contextualizar mejor el caso.

        6. DESCARGO DE RESPONSABILIDAD MÉDICA:
        Al final, incluye un aviso legal corto en cursiva aclarando que la información
        es educativa y no reemplaza una consulta médica presencial.

        TONO Y ESTILO:
        - Oraciones cortas (menos de 15 palabras cuando sea posible).
        - Habla como un médico de cabecera amable, no como un libro de texto.
        - Usa viñetas para desglosar información y negritas en palabras clave.
        ";

        $filePaths = json_decode($analysis->file_paths, true);

        if (! is_array($filePaths)) {
            $filePaths = ! empty($analysis->file_path) ? [$analysis->file_path] : [];
        }

        if (empty($filePaths)) {
            Log::error("Análisis #{$analysis->id} no tiene archivos asociados (file_paths vacío).");
            $analysis->update(['status' => 'failed']);
            return;
        }

        // --- Construcción de imágenes (independiente del proveedor) ---
        $images = [];
        $tempFilesToCleanup = [];

        foreach ($filePaths as $index => $path) {
            if (! Storage::disk('private')->exists($path)) {
                Log::warning("Análisis #{$analysis->id}: el archivo '{$path}' no existe en el disco 'private'.");
                continue;
            }

            $filePath = Storage::disk('private')->path($path);
            $mimeType = mime_content_type($filePath);

            if ($mimeType === false) {
                Log::warning("Análisis #{$analysis->id}: no se pudo determinar el mime type de '{$path}'.");
                continue;
            }

            try {
                if ($mimeType === 'application/pdf') {                    
                    $pdf = new Pdf($filePath);
                    $totalPages = $pdf->pageCount(); // Total de páginas del PDF

                    Log::info("Análisis #{$analysis->id}: PDF '{$path}' tiene {$totalPages} página(s).");

                    for ($page = 1; $page <= $totalPages; $page++) {

                        $tempImagePath = storage_path(
                            'app/temp/med_batch_' . uniqid() . '_doc' . $index . '_page' . $page . '.jpg'
                        );

                        if (! is_dir(dirname($tempImagePath))) {
                            mkdir(dirname($tempImagePath), 0755, true);
                        }

                        try {
                            $savedPaths = $pdf->selectPage($page)
                                ->format(OutputFormat::Jpg)
                                ->quality(90)
                                ->save($tempImagePath);

                            $savedPath = $savedPaths[0] ?? null;

                            if ($savedPath && file_exists($savedPath) && filesize($savedPath) > 0) {
                                $images[] = [
                                    'base64' => base64_encode(file_get_contents($savedPath)),
                                    'mime'   => 'image/jpeg',
                                ];
                                $tempFilesToCleanup[] = $savedPath;

                                Log::info("Análisis #{$analysis->id}: página {$page}/{$totalPages} convertida OK.");
                            } else {
                                Log::error("Análisis #{$analysis->id}: página {$page} de '{$path}' no generó imagen.");
                            }

                        } catch (\Throwable $e) {
                            Log::error("Análisis #{$analysis->id}: fallo página {$page} de '{$path}': " . $e->getMessage());
                        }
                    }
                } elseif (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                    $images[] = [
                        'base64' => base64_encode(file_get_contents($filePath)),
                        'mime' => $mimeType,
                    ];
                } else {
                    Log::warning("Análisis #{$analysis->id}: tipo de archivo no soportado '{$mimeType}' para '{$path}'.");
                }
            } catch (\Throwable $e) {
                Log::error("Fallo la conversión visual para el archivo {$path} en el Análisis #{$analysis->id}: " . $e->getMessage());
                continue;
            }
        }

        foreach ($tempFilesToCleanup as $tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        if (empty($images)) {
            Log::error("Cancelada la llamada a la IA para el Análisis #{$analysis->id} porque no se generaron imágenes multimedia.");
            $analysis->update(['status' => 'failed']);
            return;
        }

        if (count($images) > 20) {
            Log::warning("Análisis #{$analysis->id}: " . count($images) . " imágenes generadas, se truncan a 20 (límite API).");
            $images = array_slice($images, 0, 20);
        }

        Log::info("Análisis #{$analysis->id}: enviando " . count($images) . " imagen(es). Proveedor solicitado: " . ($provider ?? 'default de config'));

        // --- Llamada al proveedor (switch transparente) ---
        try {
            if ($withFallback) {
                // Intenta con $provider primero (o el orden default) y si falla, prueba el otro.
                $order = $provider
                    ? array_unique([$provider, $provider === 'claude' ? 'openai' : 'claude'])
                    : ['openai', 'claude'];

                $outcome = AIVisionManager::analyzeWithFallback($systemPrompt, $userText, $images, $order);
                $aiResult = $outcome['result'];
                $providerUsed = $outcome['provider_used'];
            } else {
                $driver = AIVisionManager::driver($provider);
                $aiResult = $driver->analyzeImages($systemPrompt, $userText, $images);
                $providerUsed = $driver->name();
            }

            $analysis->update([
                'ai_response' => $aiResult,
                'ai_provider' => $providerUsed, // opcional: agrega esta columna si quieres trazabilidad
                'status' => 'completed',
            ]);

            Log::info("Análisis #{$analysis->id} completado correctamente con proveedor '{$providerUsed}'.");

            // Minimización de datos: una vez que el reporte se generó y se guardó
            // exitosamente, los PDFs originales ya no son necesarios. Los borramos
            // para reducir el tiempo de retención de información médica sensible.
            $this->deleteSourceFiles($analysis, $filePaths);
        } catch (\Throwable $e) {
            Log::error("Fallo el procesamiento automático de la IA para el Análisis #{$analysis->id}: " . $e->getMessage());

            $analysis->update(['status' => 'failed']);
            // IMPORTANTE: NO borramos los PDFs aquí. Si el análisis falló, los
            // archivos originales deben conservarse para permitir un reintento.
        }
    }

    /**
     * Borra del disco los PDFs/imágenes originales usados en el análisis,
     * una vez que el reporte de IA ya fue generado y guardado exitosamente.
     *
     * Solo se debe llamar tras un status='completed' confirmado.
     */
    protected function deleteSourceFiles(MedicalAnalysis $analysis, array $filePaths): void
    {
        $eliminados = 0;
        $fallidos = [];

        foreach ($filePaths as $path) {
            try {
                if (Storage::disk('private')->exists($path)) {
                    Storage::disk('private')->delete($path);
                    $eliminados++;
                }
            } catch (\Throwable $e) {
                // Si un archivo no se pudo borrar (permisos, disco montado raro en EC2, etc.),
                // lo registramos pero NO marcamos el análisis como failed por esto:
                // el reporte ya se generó correctamente, esto es solo limpieza.
                $fallidos[] = $path;
                Log::warning("Análisis #{$analysis->id}: no se pudo borrar el archivo origen '{$path}': " . $e->getMessage());
            }
        }

        // Limpiamos también la referencia en la tabla, para dejar evidencia
        // de que los archivos ya no existen (evita futuros intentos de leerlos).
        $analysis->update([
            'file_paths' => null,
            'file_path' => null, // si todavía usas esta columna legacy
        ]);

        Log::info("Análisis #{$analysis->id}: limpieza de archivos origen completada. Eliminados: {$eliminados}/" . count($filePaths) . (empty($fallidos) ? '' : '. Fallidos: ' . implode(', ', $fallidos)));
    }

    /**
     * Muestra el análisis médico identificado por su access_token público.
     * Laravel ya resolvió $analysis automáticamente buscando por access_token;
     * si no existe, lanza 404 antes de que este método se ejecute.
     */
    public function show(MedicalAnalysis $medicalAnalysis)
    {
        $analysis = $medicalAnalysis;
    
        // Si el registro quedó inconsistente (status=completed pero sin ai_response
        // por alguna falla silenciosa), lo tratamos igual que failed para no romper la vista.
        if ($analysis->status === 'completed' && empty($analysis->ai_response)) {
            Log::warning("Análisis #{$analysis->id}: status=completed pero ai_response está vacío. Se trata como inconsistente.");
            $analysis->status = 'failed'; // solo en memoria, no se persiste
        }
        
        $price = Setting::get('medical_analysis_price', 19000); 
    
        return view('medical-analysis.show', compact('analysis', 'price'));
    }

    public function preparePayment(Request $request)
    {
        // 1. Validar que el ID de la orden exista en la base de datos
        $request->validate([
            'order_id' => 'required|exists:medical_analyses,id'
        ]);

        $id = strip_tags($request->order_id);
        $analysis = MedicalAnalysis::findOrFail($id);        

        // 2. Generar la referencia única basada en el ID y guardarla en la tabla
        $prefix = Carbon::now()->format('ymdH');                
        $random = strtoupper(Str::random(5));                                
        $paymentReference = $analysis->id . "-" . $prefix . "-" . $random;

        $analysis->update(['payment_id' => $paymentReference]);

        // 3. Convertir el total a centavos enteros (regla obligatoria de Wompi)
        $amountInCents = (int) ($analysis->price * 100); 
        $currency = 'COP';

        // 4. Calcular la firma de integridad concatenando los valores requeridos por Wompi
        $stringPayload = $paymentReference . $amountInCents . $currency . config('services.wompi.integrity_secret');
        $signatureIntegrity = hash('sha256', $stringPayload);

        // 5. Devolver los datos firmados en formato JSON hacia el frontend
        return response()->json([
            'status'              => 'success',
            'public_key'          => config('services.wompi.public_key'),
            'currency'            => $currency,
            'amount_in_cents'     => $amountInCents,
            'reference'           => $paymentReference,
            'signature_integrity' => $signatureIntegrity,
            'token'               => $analysis->access_token,
            'redirect_url'        => route('medical-analysis.payment.result', $analysis->access_token), // Tu ruta local de retorno
        ]);
    }

    public function processPaymentResult(Request $request, $token)
    {
        // 1. Buscar el análisis médico por su token de acceso único
        $analysis = MedicalAnalysis::where('access_token', $token)->firstOrFail();
        $transactionId = $request->query('id');

        if (!$transactionId) {
            return redirect()->route('home')->with('error', 'Falta el identificador del pago.');
        }

        // 2. Detectar entorno de Wompi automáticamente
        $baseUrl = 'https://production.wompi.co/v1';
        $paymentStatus = 'ERROR';

        try {
            // 3. Consultar la API de Wompi en segundo plano
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.wompi.public_key')
            ])->get("{$baseUrl}/transactions/{$transactionId}");

            if ($response->successful()) {
                $paymentStatus = $response->json('data.status') ?? 'ERROR';

                // 4. Evaluar el estado de la transacción
                if ($paymentStatus === 'APPROVED') {
                    // Evitar duplicar el correo si el usuario refresca la pantalla
                    if ($analysis->payment_status !== 'completed') {
                        $analysis->update([
                            'payment_status' => 'completed'
                        ]);

                        // Enviar correo electrónico al paciente de forma segura                        
                        if ($analysis->customer_email) {
                            Mail::to($analysis->customer_email)->send(new ExamAnalysisReady($analysis));
                        }
                    }
                } elseif ($paymentStatus === 'PENDING') {
                    $analysis->update(['payment_status' => 'pending']);
                } else {
                    $analysis->update(['payment_status' => 'failed']);
                }
            }
        } catch (\Exception $e) {
            logger()->error('Error en Callback de Wompi: ' . $e->getMessage());
            $paymentStatus = 'ERROR';
            $analysis->update(['payment_status' => 'error']);
        }

        // 5. Retornar la nueva vista independiente con los estados
        return view('medical-analysis.payment-result', compact('analysis', 'paymentStatus'));
    }
}
