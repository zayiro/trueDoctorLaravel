<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Botón «Conservar mi cuenta» del correo de aviso (enlace firmado). Página pública: nunca muestra un error crudo.
 */
class KeepAccountController extends Controller
{
    public function __invoke(Request $request, string $user): Response
    {
        $ok = false;

        if (ctype_digit($user) && $request->hasValidSignature()) {
            $ok = DB::table('users')->where('id', (int) $user)->where('role', 'patient')
                ->update(['last_engaged_at' => now(), 'cleanup_notified_at' => null]) > 0;

            if ($ok) {
                Log::info('accounts.cleanup.kept', ['user_id' => (int) $user]);
            }
        }

        $t = (array) config('account_cleanup.texts');

        return response()->view('account.kept', [
            'ok' => $ok,
            'title' => $ok ? $t['kept_title'] : $t['invalid_title'],
            'text' => $ok ? $t['kept_text'] : $t['invalid_text'],
            'homeLabel' => $t['home'],
            'homeUrl' => url('/'),
        ])->withHeaders([
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'Cache-Control' => 'no-store, private',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
