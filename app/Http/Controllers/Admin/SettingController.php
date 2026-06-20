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
        // Obtenemos todas las configuraciones agrupadas para la vista
        $settings = Setting::all()->pluck('value', 'key');

        return view('administrator.settings.index', compact('settings'));
    }

    /**
     * Actualiza las configuraciones recibidas.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'medical_analysis_price' => 'required|numeric|min:0',
            'support_email' => 'required|email',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'saas']
            );
        }

        return redirect()->back()->with('success', 'Configuraciones del SaaS actualizadas con éxito.');
    }
}
