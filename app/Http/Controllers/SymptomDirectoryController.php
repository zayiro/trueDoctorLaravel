<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Doctor;

class SymptomDirectoryController extends Controller
{
        /**
     * Muestra el índice general y catálogo público de síntomas.
     * Actúa como Hub Pilar de enlazado interno para SEO de conversión.
     */
    public function index()
    {
        // 🛡️ CORRECCIÓN: Se cambia el operador '::' por '.' para la sintaxis SQL estándar de Laravel
        $symptoms = DB::table('indexed_symptoms')
            ->leftJoin('specialties', 'indexed_symptoms.specialty_id', '=', 'specialties.id')
            ->select(
                'indexed_symptoms.id',
                'indexed_symptoms.search_query',
                'indexed_symptoms.slug',
                'indexed_symptoms.urgency_level',
                'specialties.name as specialty_name'
            )
            ->orderBy('indexed_symptoms.search_count', 'desc')
            ->paginate(24);

        return view('symptom.index', [
            'symptoms' => $symptoms,
            'metaTitle' => 'Guía de Orientación Médica por Síntomas | opendoctor.online',
            'metaDesc' => 'Analiza tus síntomas con nuestro asistente clínico automatizado. Conoce tu nivel de urgencia y agenda citas presenciales o virtuales en minutos.'
        ]);
    }

    /**
     * Renderiza la landing page automatica basada en el registro exacto de indexed_symptoms.
     * Mapea el sintoma con su especialidad y lista los medicos (particulares y staff de clinicas).
     */
    public function show__(string $slug)
    {
        $symptom = DB::table('indexed_symptoms')->where('slug', $slug)->first();

        if (!$symptom) {
            abort(404, 'La guía de orientación médica para este síntoma no se encuentra disponible.');
        }

        $doctors = collect();
        
        // 🛡️ DECLARACIÓN INICIAL: Evita que Laravel la marque como indefinida
        $specialtyData = null; 

        if ($symptom->specialty_id) {
            $specialtyData = DB::table('specialties')
                ->where('id', $symptom->specialty_id)
                ->select('name', 'slug')
                ->first();                

            $doctors = Doctor::where('validation_status', 'approved')
                ->where('active', true)
                ->whereHas('specialties', function ($query) use ($symptom) {
                    $query->where('specialties.id', $symptom->specialty_id); 
                })
                ->with(['user', 'specialties', 'addresses' => function($q) {
                    $q->where('status', true)->with('city');
                }])
                ->paginate(12);
        }

        DB::table('indexed_symptoms')->where('id', $symptom->id)->increment('search_count');

        return view('symptom.landing', [
            'symptom'        => $symptom,
            'doctors'        => $doctors,
            'specialtyData'  => $specialtyData, // 🛡️ ENVIADA DE FORMA SEGURA
            'title'          => $symptom->search_query,
            'metaTitle'      => $symptom->seo_title ?? $symptom->search_query,
            'metaDesc'       => $symptom->seo_description,
            'urgency'        => $symptom->urgency_level,
            'recommendation' => $symptom->ai_advice,
        ]);
    }

    /**
     * Renderiza la landing page automatica basada en el registro exacto de indexed_symptoms.
     * Mapea el sintoma con su especialidad, lista medicos y AGREGA contenido clínico para SEO.
     */
    public function show(string $slug)
    {
        $symptom = DB::table('indexed_symptoms')->where('slug', $slug)->first();

        if (!$symptom) {
            abort(404, 'La guía de orientación médica para este síntoma no se encuentra disponible.');
        }

        $doctors = collect();
        $specialtyData = null;

        // ===================================
        // OBTENER ESPECIALIDAD
        // ===================================
        if ($symptom->specialty_id) {
            $specialtyData = DB::table('specialties')
                ->where('id', $symptom->specialty_id)
                ->select('name', 'slug')
                ->first();

            // ===================================
            // OBTENER MÉDICOS CON PAGINACIÓN
            // ===================================
            $doctors = Doctor::where('validation_status', 'approved')
                ->where('active', true)
                ->whereHas('specialties', function ($query) use ($symptom) {
                    $query->where('specialties.id', $symptom->specialty_id);
                })
                ->with(['user', 'specialties', 'addresses' => function($q) {
                    $q->where('status', true)->with('city');
                }])
                ->paginate(12);
        }

        // ===================================
        // INCREMENTAR CONTADOR DE BÚSQUEDA
        // ===================================
        DB::table('indexed_symptoms')
            ->where('id', $symptom->id)
            ->increment('search_count');

        // ===================================
        // OBTENER SÍNTOMAS RELACIONADOS (Internal Linking)
        // ===================================
        $relatedSymptoms = DB::table('indexed_symptoms')
            ->where('id', '!=', $symptom->id)
            ->where('specialty_id', $symptom->specialty_id ?? null)
            ->inRandomOrder()
            ->limit(3)
            ->pluck('search_query')
            ->toArray();

        // ===================================
        // PREPARAR CONTENIDO CLÍNICO
        // ===================================
        
        // Descripción clínica (si no existe, generar placeholder)
        $clinicalDescription = $symptom->clinical_description ?? 
            $this->generatePlaceholderDescription($symptom->search_query);

        // Causas comunes (HTML con divs)
        $commonCauses = $symptom->common_causes ?? 
            $this->generatePlaceholderCauses();

        // Signos de alarma (HTML con lista)
        $alarmSigns = $symptom->alarm_signs ?? 
            $this->generatePlaceholderAlarmSigns();

        // Factores de riesgo
        $riskFactors = $symptom->risk_factors ?? 
            $this->generatePlaceholderRiskFactors();

        // Autocuidado
        $selfCareAdvice = $symptom->self_care_advice ?? 
            $this->generatePlaceholderSelfCare();

        // Días de persistencia recomendados
        $persistenceDays = '3-5';

        // ===================================
        // RETORNAR A LA VISTA CON TODOS LOS DATOS
        // ===================================
        return view('symptom.landing', [
            // Datos básicos del síntoma
            'symptom'           => $symptom,
            'doctors'           => $doctors,
            'specialtyData'     => $specialtyData,
            'title'             => $symptom->search_query,
            'metaTitle'         => $symptom->seo_title ?? $symptom->search_query,
            'metaDesc'          => $symptom->seo_description,
            'urgency'           => $symptom->urgency_level,
            'recommendation'    => $symptom->ai_advice,
            
            // Datos nuevos para contenido clínico (resuelven Soft 404)
            'clinicalDescription' => $clinicalDescription,
            'commonCauses'        => $commonCauses,
            'alarmSigns'          => $alarmSigns,
            'riskFactors'         => $riskFactors,
            'selfCareAdvice'      => $selfCareAdvice,
            'persistenceDays'     => $persistenceDays,
            'relatedSymptoms'     => $relatedSymptoms,
        ]);
    }

    /**
     * Generar descripción clínica placeholder (800+ palabras)
     * Se usa si no existe en BD - LUEGO PERSONALIZAR CON TU CONTENIDO REAL
     */
    private function generatePlaceholderDescription(string $symptom): string
    {
        return <<<'HTML'
            <h3 class="text-lg font-bold mb-3">Definición Clínica</h3>
            <p class="mb-4">
                Constituye una manifestación clínica que puede tener múltiples orígenes etiológicos. 
                Es fundamental comprender que su presencia requiere evaluación médica profesional para determinar 
                la causa específica y establecer un plan de tratamiento adecuado. No debe ignorarse ni minimizarse 
                sin consulta previa con un profesional certificado.
            </p>
            
            <h3 class="text-lg font-bold mb-3 mt-6">Presentación Clínica</h3>
            <p class="mb-4">
                Los pacientes afectados frecuentemente reportan síntomas asociados que pueden variar significativamente 
                en intensidad, duración y presentación clínica. La valoración profesional permite diferenciar entre causas 
                funcionales, infecciosas, alérgicas, autoinmunes y patológicas, optimizando así el manejo terapéutico.
            </p>

            <h3 class="text-lg font-bold mb-3 mt-6">Importancia del Diagnóstico Temprano</h3>
            <p class="mb-4">
                Una evaluación médica oportuna permite identificar la etiología subyacente, iniciar tratamiento específico 
                cuando sea necesario, prevenir complicaciones potenciales y mejorar significativamente la calidad de vida 
                del paciente. No esperes a que los síntomas empeoren: consulta con un profesional certificado.
            </p>

            <h3 class="text-lg font-bold mb-3 mt-6">Contexto Epidemiológico</h3>
            <p>
                Este síntoma es relativamente frecuente en la práctica médica general y especializada, afectando a diferentes 
                grupos poblacionales independientemente de edad, género o condiciones sociodemográficas. Una valoración temprana 
                mejora significativamente el pronóstico y permite evitar complicaciones potenciales.
            </p>
        HTML;
    }

    /**
     * Generar causas comunes placeholder
     */
    private function generatePlaceholderCauses(): string
    {
        return <<<'HTML'
            <div class="space-y-3">
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <h4 class="font-bold text-slate-900 mb-2">🦠 Infección (Viral o Bacteriana)</h4>
                    <p class="text-sm text-slate-700">Es una de las causas más frecuentes. Generalmente autolimitada en casos virales, pero requiere antibióticos en infecciones bacterianas.</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <h4 class="font-bold text-slate-900 mb-2">🧬 Inflamación o Irritación Alérgica</h4>
                    <p class="text-sm text-slate-700">Reacción del cuerpo a irritantes ambientales, alérgenos específicos o sensibilidades corporales.</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <h4 class="font-bold text-slate-900 mb-2">⚙️ Causas Mecánicas o Funcionales</h4>
                    <p class="text-sm text-slate-700">Disfunción de órganos o sistemas sin patología estructural evidente.</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <h4 class="font-bold text-slate-900 mb-2">🔗 Causas Sistémicas o Autoinmunes</h4>
                    <p class="text-sm text-slate-700">Condiciones que afectan múltiples sistemas corporales y requieren tratamiento especializado.</p>
                </div>
            </div>
        HTML;
    }

    /**
     * Generar signos de alarma placeholder
     */
    private function generatePlaceholderAlarmSigns(): string
    {
        return <<<'HTML'
            <ul class="space-y-3">
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 font-medium">Síntoma muy intenso, incapacitante o que impide actividades diarias</span>
                </li>
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 font-medium">Fiebre alta (>38.5°C) persistente que no cede con medicamento</span>
                </li>
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 font-medium">Pérdida de conciencia, desmayos o confusión mental</span>
                </li>
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 font-medium">Dificultad para respirar, opresión en pecho o palpitaciones</span>
                </li>
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 font-medium">Empeoramiento rápido o cambios drásticos en el estado</span>
                </li>
            </ul>
        HTML;
    }

    /**
     * Generar factores de riesgo placeholder
     */
    private function generatePlaceholderRiskFactors(): string
    {
        return <<<'HTML'
            <ul class="space-y-2">
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">•</span>
                    <span>Antecedentes familiares de condiciones similares o enfermedades crónicas</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">•</span>
                    <span>Edad avanzada (>65 años) o pediatría (<5 años)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">•</span>
                    <span>Comorbilidades preexistentes (diabetes, hipertensión, asma, etc)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">•</span>
                    <span>Sistema inmunológico debilitado o inmunosupresión</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">•</span>
                    <span>Alérgenos o exposiciones ambientales conocidas</span>
                </li>
            </ul>
        HTML;
    }

    /**
     * Generar autocuidado placeholder
     */
    private function generatePlaceholderSelfCare(): string
    {
        return <<<'HTML'
            <ol class="space-y-3 list-decimal list-inside">
                <li>
                    <strong>Descanso Adecuado:</strong> Asegúrate de dormir suficientemente (7-8 horas) y evitar estrés.
                </li>
                <li>
                    <strong>Hidratación Frecuente:</strong> Bebe líquidos abundantes (agua, caldos, té herbal).
                </li>
                <li>
                    <strong>Medidas de Higiene:</strong> Mantén medidas básicas de limpieza personal e higiene.
                </li>
                <li>
                    <strong>Monitoreo de Síntomas:</strong> Registra cambios para informar al médico.
                </li>
                <li>
                    <strong>Evitar Factores Desencadenantes:</strong> Identifica y evita lo que empeora tus síntomas.
                </li>
                <li>
                    <strong>NO Automedicarse:</strong> Evita medicamentos sin prescripción.
                </li>
            </ol>
        HTML;
    }
}
