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
    private const AI_MODELS = \App\Services\AiModels\AiModelResolver::EXAM_OPTIONS;

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
    ];

    /** Comisión de la pasarela (fórmula: % + valor fijo, más IVA sobre esa comisión). Se usa en todos los módulos (citas, exámenes, piel y Chat de Salud IA). */
    private const GATEWAY = [
        'ai_chat_wompi_percent' => ['Porcentaje de Wompi (%)', 2.65, '0.01', 0, 10, 'Tarjeta: 2,65 %. Con Puntos Colombia sube a 4,43 %.'],
        'ai_chat_wompi_fixed_fee' => ['Valor fijo de Wompi por transacción (COP)', 700, '1', 0, 5000, 'Confírmalo con tu contrato de Wompi.'],
        'ai_chat_wompi_iva_percent' => ['IVA sobre la comisión de Wompi (%)', 19, '1', 0, 30, 'Pon 0 si tu tarifa de Wompi ya lo incluye.'],
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
            'skinModels' => \App\Services\AiModels\AiModelResolver::SKIN_OPTIONS,
            'skinModelCurrent' => \App\Services\AiModels\AiModelResolver::skinModel(),
            'commissions' => self::COMMISSIONS,
            'gateway' => self::GATEWAY,
            'company' => self::COMPANY,
            'smsProviders' => app(\App\Sms\SmsManager::class)->options(),
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

        $schema[\App\Services\AiModels\AiModelResolver::SKIN_KEY] = [
            'group' => 'ai',
            'rules' => ['nullable', Rule::in(array_keys(\App\Services\AiModels\AiModelResolver::SKIN_OPTIONS))],
        ];

        // Proveedor de SMS: principal y respaldo. Opcionales a propósito: si la vista no los envía, el guardado sigue funcionando.
        $smsKeys = array_keys(app(\App\Sms\SmsManager::class)->options());
        $schema['sms_provider'] = ['group' => 'sms', 'rules' => ['nullable', Rule::in($smsKeys)]];
        $schema['sms_fallback'] = ['group' => 'sms', 'rules' => ['nullable', Rule::in($smsKeys)]];

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

        foreach (self::GATEWAY as $field => [$label, $default, $step, $min, $max, $help]) {
            $schema[$field] = [
                'group' => 'ai_chat',
                'rules' => ['required', 'numeric', "min:{$min}", "max:{$max}"],
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
