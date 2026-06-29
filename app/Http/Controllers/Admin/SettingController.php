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
     * Actualiza las configuraciones recibidas.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            // SaaS
            'medical_analysis_price' => 'required|numeric|min:0',
            'support_email'          => 'required|email',

            // Comisiones
            'virtual_commission_doctor'        => 'required|numeric|min:0|max:100',
            'virtual_commission_clinic'        => 'required|numeric|min:0|max:100',
            'presential_commission_doctor'     => 'required|numeric|min:0|max:100',
            'presential_commission_clinic'     => 'required|numeric|min:0|max:100',
            'wompi_fee'                        => 'required|numeric|min:0|max:100',
        ]);

        $groups = [
            'medical_analysis_price'           => 'saas',
            'support_email'                    => 'saas',
            'virtual_commission_doctor'        => 'commissions',
            'virtual_commission_clinic'        => 'commissions',
            'presential_commission_doctor'     => 'commissions',
            'presential_commission_clinic'     => 'commissions',
            'wompi_fee'                        => 'commissions',
        ];

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $groups[$key] ?? 'general']
            );
        }

        return redirect()->back()->with('success', 'Configuraciones actualizadas con éxito.');
    }
}
