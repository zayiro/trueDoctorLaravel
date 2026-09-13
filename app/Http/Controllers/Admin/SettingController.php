<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Muestra el panel de configuración del SaaS.
     */
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('administrator.settings.index', compact('settings'));
    }

    /**
     * Actualiza todas las configuraciones de forma consolidada
     */
    public function update(Request $request)
    {
        $data = $request->validate([            
            // PRECIOS DE EXÁMENES (NUEVO SISTEMA)
            'exam_type_lab_price' => 'required|integer|min:5000',
            'exam_type_xray_price' => 'required|integer|min:5000',
            'exam_type_ultrasound_price' => 'required|integer|min:5000',
            'exam_type_ct_price' => 'required|integer|min:5000',
            'exam_type_mri_price' => 'required|integer|min:5000',
            'exam_type_dicom_price' => 'required|integer|min:5000',
            'exam_type_mammography_price' => 'required|integer|min:5000',

            // FACTORES DE DECIMACIÓN
            'ct_auto_decimation' => 'required|integer|min:1|max:100',
            'mri_auto_decimation' => 'required|integer|min:1|max:100',
            'xray_auto_decimation' => 'required|integer|min:1|max:100',
            'ultrasound_auto_decimation' => 'required|integer|min:1|max:100',
            'dicom_auto_decimation' => 'required|integer|min:1|max:100',
            'mammography_auto_decimation' => 'required|integer|min:1|max:100',

            'lab_ai_primary_model' => 'required|in:gpt-4o,claude-sonnet-4-5',
            'lab_ai_fallback_model' => 'required|in:gpt-4o,claude-sonnet-4-5',
            'imaging_ai_primary_model' => 'required|in:gpt-4o,claude-sonnet-4-5',
            'imaging_ai_fallback_model' => 'required|in:gpt-4o,claude-sonnet-4-5',

            // SAAS GENERAL
            'support_email' => 'required|email',

            // COMISIONES
            'virtual_commission_doctor' => 'required|numeric|min:0|max:100',
            'virtual_commission_clinic' => 'required|numeric|min:0|max:100',
            'presential_commission_doctor' => 'required|numeric|min:0|max:100',
            'presential_commission_clinic' => 'required|numeric|min:0|max:100',
            'wompi_fee' => 'required|numeric|min:0|max:100',
        ]);

        // Mapeo de grupos para cada configuración
        $groups = [
            // PRECIOS
            'exam_type_lab_price' => 'pricing',
            'exam_type_xray_price' => 'pricing',
            'exam_type_ultrasound_price' => 'pricing',
            'exam_type_ct_price' => 'pricing',
            'exam_type_mri_price' => 'pricing',
            'exam_type_dicom_price' => 'pricing',
            'exam_type_mammography_price' => 'pricing',

            // DECIMACIÓN
            'ct_auto_decimation' => 'analysis',
            'mri_auto_decimation' => 'analysis',
            'xray_auto_decimation' => 'analysis',
            'ultrasound_auto_decimation' => 'analysis',
            'dicom_auto_decimation' => 'analysis',
            'mammography_auto_decimation' => 'analysis',

            'lab_ai_primary_model' => 'ai',
            'lab_ai_fallback_model' => 'ai',
            'imaging_ai_primary_model' => 'ai',
            'imaging_ai_fallback_model' => 'ai',

            // SAAS
            'support_email' => 'saas',

            // COMISIONES
            'virtual_commission_doctor' => 'commissions',
            'virtual_commission_clinic' => 'commissions',
            'presential_commission_doctor' => 'commissions',
            'presential_commission_clinic' => 'commissions',
            'wompi_fee' => 'commissions',
        ];

        // Actualizar todas las configuraciones en una sola pasada
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => $groups[$key] ?? 'general'
                ]
            );
        }

        return redirect()->back()->with('success', '✅ Todas las configuraciones han sido actualizadas correctamente.');
    }
}