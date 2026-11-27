<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Enlace del correo «Crea tu contraseña». Fortify solo deja abrir la página de restablecer a quien NO tiene
 * sesión; como el paciente suele seguir con la sesión abierta justo después de reservar, aquí se cierra esa sesión
 * y se lo lleva a la página normal de restablecer (mismo token de un solo uso).
 */
class PasswordSetupController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $email = (string) $request->query('email', '');

        if (! preg_match('/^[A-Za-z0-9]{20,100}$/', $token) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('password.request');
        }

        if (Auth::check()) {
            Auth::guard(config('fortify.guard', 'web'))->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return redirect()->route('password.reset', ['token' => $token, 'email' => $email]);
    }
}
