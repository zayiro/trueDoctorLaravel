<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

/** Al confirmar el correo, lleva al panel con un aviso amable (Fortify redirige a «/» por defecto). */
class VerifiedEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request)
    {
        $to = url('/');
        foreach (['admin.dashboard', 'dashboard'] as $name) {
            if (Route::has($name)) {
                $to = route($name);
                break;
            }
        }

        return redirect($to)->with('status', 'correo-verificado');
    }
}
