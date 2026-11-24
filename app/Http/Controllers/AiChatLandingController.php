<?php

namespace App\Http\Controllers;

use App\Services\AiChat\AiChatPricing;
use App\Services\AiChat\AiChatSettings;
use App\Services\AiChat\AiChatWaitlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Throwable;

/** Página pública (indexable) del Chat de Salud con IA y la lista de espera de lanzamiento. */
class AiChatLandingController extends Controller
{
    public function show(): View
    {
        return view('ai-chat.landing', $this->viewData(AiChatSettings::salesPaused()));
    }

    /** @return array<string, mixed> */
    public function viewData(bool $paused): array
    {
        $plans = [];
        foreach (config('ai_chat.durations') as $minutes) {
            $minutes = (int) $minutes;
            $quote = AiChatPricing::quote($minutes);
            $plans[] = [
                'minutes' => $minutes,
                'total' => $quote['total'],
                'messages' => AiChatSettings::maxMessages($minutes),
            ];
        }

        return [
            'paused' => $paused,
            'plans' => $plans,
            'expiryDays' => max(1, AiChatSettings::int('ai_chat_expiry_days')),
            'retentionDays' => max(1, AiChatSettings::int('ai_chat_retention_days')),
        ];
    }

    public function waitlist(Request $request, AiChatWaitlistService $waitlist): RedirectResponse
    {
        $back = route('ai-chat.landing').'#aviso';

        // Trampa para robots: un campo oculto que una persona nunca llena. Se responde igual de amable.
        if (filled($request->input('website'))) {
            return redirect($back)->with('waitlist_ok', true);
        }

        try {
            if (! $waitlist->add((string) $request->input('email'))) {
                return redirect($back)->with('waitlist_error', 'Revisa tu correo: parece que le falta algo. Escríbelo de nuevo, por favor.')->withInput($request->only('email'));
            }
        } catch (Throwable $e) {
            Log::warning('ai_chat.waitlist_failed', ['message' => $e->getMessage()]);

            return redirect($back)->with('waitlist_error', 'No pudimos guardar tu correo en este momento. Inténtalo de nuevo en unos minutos.');
        }

        return redirect($back)->with('waitlist_ok', true);
    }
}
