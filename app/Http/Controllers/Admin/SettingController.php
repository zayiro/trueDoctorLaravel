<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    /** Tipos de examen (clave => etiqueta). Define precios y, salvo laboratorio, decimación. */
    private const EXAM_TYPES = [
        'lab' => '🧪 Laboratorio',
        'xray' => '📸 Radiografía',
        'ultrasound' => '🔊 Ecografía',
        'ct' => '📊 Tomografía',
        'mri' => '🧠 Resonancia Magnética',
        'dicom' => '💾 DICOM',
        'mammography' => '🎀 Mamografía',
    ];

    /** Modelos de IA que el panel permite elegir (valor guardado => nombre). */
    private const AI_MODELS = [
        'gpt-4o' => 'GPT-4o (OpenAI)',
        'claude-sonnet-4-5' => 'Claude Sonnet 4.5',
    ];

    /** Un modelo primario y uno de respaldo por cada grupo de análisis. */
    private const AI_FIELDS = [
        'lab_ai_primary_model',
        'lab_ai_fallback_model',
        'imaging_ai_primary_model',
        'imaging_ai_fallback_model',
    ];

    /** Datos de empresa que salen en el encabezado de los soportes de liquidación (clave => etiqueta). */
    private const COMPANY = [
        'company_legal_name' => 'Razón social',
        'company_nit' => 'NIT',
        'company_address' => 'Dirección',
        'company_city' => 'Ciudad',
        'company_email' => 'Correo de la empresa',
    ];

    /** Comisiones (clave => [etiqueta, valor por defecto]). */
    private const COMMISSIONS = [
        'virtual_commission_doctor' => ['Comisión Cita Virtual - Doctor (%)', 0],
        'virtual_commission_clinic' => ['Comisión Cita Virtual - Clínica (%)', 0],
        'presential_commission_doctor' => ['Comisión Cita Presencial - Doctor (%)', 0],
        'presential_commission_clinic' => ['Comisión Cita Presencial - Clínica (%)', 0],
        'wompi_fee' => ['Fee Wompi (%)', 2.9],
    ];

    /**
     * Muestra el panel de configuración del SaaS.
     */
    public function index(): View
    {
        return view('administrator.settings.index', [
            'settings' => Setting::all()->pluck('value', 'key'),
            'examTypes' => self::EXAM_TYPES,
            'decimationTypes' => $this->decimationTypes(),
            'aiModels' => self::AI_MODELS,
            'commissions' => self::COMMISSIONS,
            'company' => self::COMPANY,
        ]);
    }

    /**
     * Valida y guarda todas las configuraciones de una sola vez.
     */
    public function update(Request $request): RedirectResponse
    {
        $schema = $this->schema();

        // Las reglas salen del mismo esquema que define el grupo de cada ajuste.
        $data = $request->validate(Arr::map($schema, fn (array $definition) => $definition['rules']));

        DB::transaction(function () use ($data, $schema) {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ?? '', 'group' => $schema[$key]['group']]
                );
            }
        });

        return redirect()->back()->with('success', '✅ Todas las configuraciones han sido actualizadas correctamente.');
    }

    /**
     * Esquema único de ajustes: clave => [grupo, reglas de validación].
     * Agregar un ajuste nuevo es agregarlo aquí (y mostrarlo en la vista).
     */
    private function schema(): array
    {
        $schema = [];

        foreach (array_keys(self::EXAM_TYPES) as $type) {
            $schema["exam_type_{$type}_price"] = [
                'group' => 'pricing',
                'rules' => ['required', 'integer', 'min:5000'],
            ];
        }

        $schema['skin_analysis_price'] = [
            'group' => 'skin_analysis',
            'rules' => ['required', 'integer', 'min:5000', 'max:500000'],
        ];

        foreach (array_keys($this->decimationTypes()) as $type) {
            $schema["{$type}_auto_decimation"] = [
                'group' => 'analysis',
                'rules' => ['required', 'integer', 'min:1', 'max:100'],
            ];
        }

        foreach (self::AI_FIELDS as $field) {
            $schema[$field] = [
                'group' => 'ai',
                'rules' => ['required', Rule::in(array_keys(self::AI_MODELS))],
            ];
        }

        $schema['support_email'] = [
            'group' => 'saas',
            'rules' => ['required', 'email'],
        ];

        foreach (array_keys(self::COMMISSIONS) as $field) {
            $schema[$field] = [
                'group' => 'commissions',
                'rules' => ['required', 'numeric', 'min:0', 'max:100'],
            ];
        }

        foreach (self::COMPANY as $field => $label) {
            $schema[$field] = [
                'group' => 'company',
                'rules' => $field === 'company_email' ? ['nullable', 'email', 'max:150'] : ['nullable', 'string', 'max:' . ($field === 'company_nit' ? 30 : 150)],
            ];
        }

        return $schema;
    }

    /** El laboratorio no se decima: solo los estudios de imagen. */
    private function decimationTypes(): array
    {
        return Arr::except(self::EXAM_TYPES, ['lab']);
    }
}
