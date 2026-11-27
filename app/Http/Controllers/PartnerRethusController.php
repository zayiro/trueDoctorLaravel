<?php

namespace App\Http\Controllers;

use App\Services\Rethus\RethusVerificationService;
use Illuminate\Support\Facades\Auth;

/** El doctor ve el estado de su verificación ReTHUS y la solicita (opcional). */
class PartnerRethusController extends Controller
{
    public function show()
    {
        $doctor = Auth::user()?->doctor;
        if (!$doctor) {
            return redirect()->route('profile.show')->with('error', 'No encontramos un perfil profesional asociado a tu cuenta.');
        }

        return view('partner.rethus.show', ['doctor' => $doctor->load('rethusLogs')]);
    }

    public function request(RethusVerificationService $service)
    {
        $doctor = Auth::user()?->doctor;
        if (!$doctor) {
            return redirect()->route('profile.show')->with('error', 'No encontramos un perfil profesional asociado a tu cuenta.');
        }

        $r = $service->request($doctor);

        return redirect()->route('partner.rethus.show')->with($r['ok'] ? 'success' : 'error', $r['message']);
    }
}
