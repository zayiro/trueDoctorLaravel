<?php

namespace App\Http\Controllers;

use App\Mail\AiChatPurchased;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Services\AiChat\AiChatReportService;
use App\Services\AiChat\AiChatReportText;
use App\Services\AiChat\AiChatResponder;
use App\Services\AiChat\AiChatSessionService;
use App\Services\AiChat\AiChatSettings;
use App\Services\AiChat\AiChatSpecialties;
use App\Services\AiChat\AiChatText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Entrada, inicio y chat de una sesión. El acceso es por el token del enlace + identificación,
 * o directo si quien entra es el paciente dueño con su cuenta.
 * Páginas públicas: nunca muestran un error crudo, siempre un mensaje amable.
 */
class AiChatSessionController extends Controller
{
    public function __construct(
        private readonly AiChatSessionService $sessions,
        private readonly AiChatResponder $responder,
    ) {
    }

    // ------------------------------------------------------------------
    // Entrada y validación de identificación
    // ------------------------------------------------------------------

    public function enter(Request $request, string $token): Response|RedirectResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return $this->invalid($request);
        }

        $session = $this->sessions->refresh($session);

        // Terminada: el informe es información de salud, así que se pide la identificación antes de mostrarlo.
        if ($session->status === AiChatSession::STATUS_FINISHED && $this->canAccess($session)) {
            return redirect()->route('ai-chat.report', ['token' => $token]);
        }

        $redirect = $session->status === AiChatSession::STATUS_FINISHED ? null : $this->routeByStatus($session);
        if ($redirect) {
            return $redirect;
        }

        if ($this->canAccess($session)) {
            return redirect()->route($session->status === AiChatSession::STATUS_ACTIVE ? 'ai-chat.chat' : 'ai-chat.start', ['token' => $token]);
        }

        return $this->page('ai-chat.verify', [
            'locale' => $session->locale,
            'token' => $token,
            'locked' => $session->id_locked_until && $session->id_locked_until->isFuture(),
        ]);
    }

    public function verify(Request $request, string $token): RedirectResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return redirect()->route('ai-chat.buy');
        }

        $locale = $session->locale;
        $session = $this->sessions->refresh($session);

        if (! in_array($session->status, [AiChatSession::STATUS_PENDING, AiChatSession::STATUS_ACTIVE, AiChatSession::STATUS_FINISHED], true)) {
            return redirect()->route('ai-chat.enter', ['token' => $token]);
        }

        [$result, $left] = $this->sessions->checkIdentification($session, (string) $request->input('identification'));

        if ($result === 'ok') {
            session()->put($this->accessKey($session), true);

            $next = match ($session->status) {
                AiChatSession::STATUS_ACTIVE => 'ai-chat.chat',
                AiChatSession::STATUS_FINISHED => 'ai-chat.report',
                default => 'ai-chat.start',
            };

            return redirect()->route($next, ['token' => $token]);
        }

        $message = match ($result) {
            'bad' => AiChatText::t('verify_bad', ['left' => $left], $locale),
            'locked' => AiChatText::t('verify_locked', [], $locale),
            default => AiChatText::t('verify_noid', [], $locale),
        };

        return redirect()->route('ai-chat.enter', ['token' => $token])->with('error', $message);
    }

    /** Reenvía el enlace al correo de la compra (una vez cada 10 minutos). */
    public function resend(Request $request, string $token): RedirectResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return redirect()->route('ai-chat.buy');
        }

        $locale = $session->locale;

        if (! in_array($session->status, [AiChatSession::STATUS_PENDING, AiChatSession::STATUS_ACTIVE], true)) {
            return redirect()->route('ai-chat.enter', ['token' => $token]);
        }

        if (! Cache::add('ai-chat-resend:' . $session->id, 1, now()->addMinutes(10))) {
            return redirect()->route('ai-chat.enter', ['token' => $token])->with('status', AiChatText::t('resend_wait', [], $locale));
        }

        try {
            if ($session->user?->email) {
                \Illuminate\Support\Facades\Mail::to($session->user->email)->queue(new AiChatPurchased($session));
            }
        } catch (Throwable $e) {
            Log::warning('ai_chat.resend_failed', ['session_id' => $session->id, 'message' => $e->getMessage()]);
        }

        // Mismo mensaje exista o no el correo: no se revela nada.
        return redirect()->route('ai-chat.enter', ['token' => $token])->with('status', AiChatText::t('resend_done', [], $locale));
    }

    // ------------------------------------------------------------------
    // Vista de inicio y botón "Empezar"
    // ------------------------------------------------------------------

    public function start(Request $request, string $token): Response|RedirectResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return $this->invalid($request);
        }

        $session = $this->sessions->refresh($session);

        if (! $this->canAccess($session)) {
            return redirect()->route('ai-chat.enter', ['token' => $token]);
        }

        if ($session->status === AiChatSession::STATUS_ACTIVE) {
            return redirect()->route('ai-chat.chat', ['token' => $token]);
        }

        $redirect = $this->routeByStatus($session, true);
        if ($redirect) {
            return $redirect;
        }

        $locale = $session->locale;
        $expires = $session->expires_at
            ? $session->expires_at->copy()->timezone('America/Bogota')->locale($locale)
                ->translatedFormat($locale === 'en' ? 'F j, Y' : 'j \d\e F \d\e Y')
            : null;

        return $this->page('ai-chat.start', [
            'locale' => $locale,
            'session' => $session,
            'token' => $token,
            'expires' => $expires,
        ]);
    }

    public function begin(Request $request, string $token): RedirectResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return redirect()->route('ai-chat.buy');
        }

        if (! $this->canAccess($session)) {
            return redirect()->route('ai-chat.enter', ['token' => $token]);
        }

        $this->sessions->begin($session);

        // Si ya había empezado (doble clic) o no se pudo, enter decide a dónde ir con el estado real.
        return redirect()->route('ai-chat.enter', ['token' => $token]);
    }

    // ------------------------------------------------------------------
    // Informe final
    // ------------------------------------------------------------------

    public function report(Request $request, string $token): Response|RedirectResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return $this->invalid($request);
        }

        $session = $this->sessions->refresh($session);

        if (! $this->canAccess($session) || $session->status !== AiChatSession::STATUS_FINISHED) {
            return redirect()->route('ai-chat.enter', ['token' => $token]);
        }

        $locale = $session->locale;
        $report = AiChatReportService::content($session);

        $state = match (true) {
            $session->purged_at !== null => 'purged',
            $report !== null => 'ready',
            $session->report_status === AiChatSession::REPORT_SKIPPED => 'skipped',
            $session->report_status === AiChatSession::REPORT_FAILED => 'failed',
            default => 'preparing',
        };

        $data = [
            'locale' => $locale,
            'state' => $state,
            'report' => $report ?? [],
            'token' => $token,
            'date' => ($session->ended_at ?? $session->end_time)
                ? ($session->ended_at ?? $session->end_time)->copy()->timezone('America/Bogota')->locale($locale)
                    ->translatedFormat($locale === 'en' ? 'F j, Y' : 'j \d\e F \d\e Y')
                : '',
            'purgeDate' => $session->purge_at ? AiChatReportText::longDate($session->purge_at, $locale) : '',
            'targetSlug' => null,
            'targetName' => '',
            'suggestedName' => '',
            'available' => false,
            'hasSuggested' => false,
        ];

        if ($report) {
            $rec = AiChatReportService::recommendation($report['specialty'] ?? null);
            $data['targetSlug'] = $rec['target']?->slug;
            $data['targetName'] = AiChatReportService::specialtyName($rec['target'], $locale);
            $data['suggestedName'] = AiChatReportService::specialtyName($rec['suggested'], $locale);
            $data['available'] = $rec['available'];
            $data['hasSuggested'] = (bool) $rec['suggested'];
        }

        return $this->page('ai-chat.report', $data);
    }

    /** Descarga de la conversación en un archivo de texto (la copia personal que el paciente guarda). */
    public function download(Request $request, string $token): Response|RedirectResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return $this->invalid($request);
        }

        $session = $this->sessions->refresh($session);

        if (! $this->canAccess($session) || $session->status !== AiChatSession::STATUS_FINISHED || $session->purged_at) {
            return redirect()->route('ai-chat.enter', ['token' => $token]);
        }

        $locale = $session->locale;
        $t = fn (string $key) => AiChatReportText::t($key, [], $locale);
        $when = $session->ended_at ?? $session->end_time;

        $lines = [
            $t('download_title'),
            $t('download_date').': '.($when ? AiChatReportText::longDate($when, $locale) : ''),
            str_repeat('-', 48),
            '',
        ];

        foreach ($session->messages()->whereIn('kind', ['chat', 'quickstart'])->where('off_topic', false)->orderBy('id')->get(['role', 'content']) as $m) {
            $lines[] = ($m->role === 'user' ? $t('download_you') : $t('download_ai')).':';
            $lines[] = trim($m->content);
            $lines[] = '';
        }

        $lines[] = str_repeat('-', 48);
        $lines[] = $t('disclaimer');

        $name = 'chat-salud-ia-'.($when ? $when->copy()->timezone('America/Bogota')->format('Y-m-d') : 'sesion').'.txt';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    // ------------------------------------------------------------------
    // Chat
    // ------------------------------------------------------------------

    public function chat(Request $request, string $token): Response|RedirectResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return $this->invalid($request);
        }

        $session = $this->sessions->refresh($session);

        if (! $this->canAccess($session)) {
            return redirect()->route('ai-chat.enter', ['token' => $token]);
        }

        if ($session->status !== AiChatSession::STATUS_ACTIVE) {
            return redirect()->route('ai-chat.enter', ['token' => $token]);
        }

        [$instance, $result] = $this->sessions->claimInstance($session);

        if ($result === 'limit') {
            return $this->friendly($session->locale, 'elsewhere_title', 'elsewhere_text', 'use_here', route('ai-chat.chat', ['token' => $token]));
        }

        if (! $instance) {
            return redirect()->route('ai-chat.enter', ['token' => $token]);
        }

        $session = $session->fresh();
        $locale = $session->locale;
        $t = fn (string $key, array $r = []) => AiChatText::t($key, $r, $locale);

        $messages = $session->messages()->orderBy('id')->get()->map(fn (AiChatMessage $m) => $this->payload($m))->all();

        $texts = [];
        foreach (['timer_label', 'five_min', 'one_min', 'other_title', 'other_text', 'use_here', 'ended_overlay', 'limit_reached',
                  'err_generic', 'err_too_long', 'err_busy', 'typing', 'nudge', 'msgs_left', 'input_placeholder', 'send'] as $key) {
            $texts[$key] = $t($key);
        }

        return $this->page('ai-chat.chat', [
            'locale' => $locale,
            'session' => $session,
            'config' => [
                'urls' => [
                    'message' => route('ai-chat.message', ['token' => $token]),
                    'state' => route('ai-chat.state', ['token' => $token]),
                    'locale' => route('ai-chat.locale', ['token' => $token]),
                    'release' => route('ai-chat.release', ['token' => $token]),
                ],
                'csrf' => csrf_token(),
                'instance' => $instance,
                'secondsLeft' => $session->secondsLeft(),
                'focus' => $this->focusText($session),
                'remaining' => $this->sessions->remainingMessages($session),
                'maxChars' => AiChatSettings::int('ai_chat_max_chars'),
                'nudgeSeconds' => AiChatSettings::int('ai_chat_nudge_seconds'),
                'messages' => $messages,
                'quickUsed' => $session->user_message_count > 0 || $session->messages()->where('kind', 'quickstart')->exists(),
                'texts' => $texts,
            ],
        ]);
    }

    public function message(Request $request, string $token): JsonResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return $this->json(['ok' => false, 'code' => 'invalid'], 404);
        }

        if (! $this->canAccess($session)) {
            return $this->json(['ok' => false, 'code' => 'auth'], 403);
        }

        $session = $this->sessions->refresh($session);

        if ($session->status !== AiChatSession::STATUS_ACTIVE) {
            return $this->json(['ok' => false, 'code' => 'ended'], 409);
        }

        if (! $this->sessions->isActiveInstance($session, (string) $request->input('instance'))) {
            return $this->json(['ok' => false, 'code' => 'other_device'], 409);
        }

        $locale = $session->locale;
        $lock = Cache::lock('ai-chat-send:' . $session->id, 90);

        if (! $lock->get()) {
            return $this->json(['ok' => false, 'code' => 'busy', 'message' => AiChatText::t('err_busy', [], $locale)], 429);
        }

        try {
            $quick = $request->input('quick');

            if ($quick !== null && $quick !== '') {
                $i = (int) $quick;

                // El arranque rápido solo vale al inicio, una vez, y el texto lo pone el servidor.
                if ($i < 1 || $i > 6 || $session->user_message_count > 0 || $session->messages()->where('kind', 'quickstart')->exists()) {
                    return $this->json(['ok' => false, 'code' => 'bad_request', 'message' => AiChatText::t('err_generic', [], $locale)], 422);
                }

                $content = AiChatText::t("qs_{$i}", [], $locale);
                $kind = 'quickstart';
                $counted = false;
            } else {
                $content = trim((string) $request->input('text'));
                $max = AiChatSettings::int('ai_chat_max_chars');

                if ($content === '') {
                    return $this->json(['ok' => false, 'code' => 'empty'], 422);
                }

                if (mb_strlen($content) > $max) {
                    return $this->json(['ok' => false, 'code' => 'too_long', 'message' => AiChatText::t('err_too_long', ['max' => $max], $locale)], 422);
                }

                $limit = AiChatSettings::maxMessages((int) $session->duration_minutes);
                $taken = AiChatSession::whereKey($session->id)->where('user_message_count', '<', $limit)->increment('user_message_count');

                if (! $taken) {
                    return $this->json(['ok' => false, 'code' => 'limit', 'message' => AiChatText::t('limit_reached', [], $locale)], 422);
                }

                $kind = 'chat';
                $counted = true;
            }

            $userMessage = $session->messages()->create([
                'role' => 'user',
                'kind' => $kind,
                'content' => $content,
                'counted' => $counted,
                'locale' => $locale,
            ]);

            $reply = $this->responder->reply($session->fresh(), $userMessage);
            $fresh = $session->fresh();

            return $this->json([
                'ok' => true,
                'messages' => [$this->payload($userMessage), $this->payload($reply)],
                'remaining' => $this->sessions->remainingMessages($fresh),
                'seconds_left' => $fresh->secondsLeft(),
                'focus' => $this->focusText($fresh),
            ]);
        } catch (Throwable $e) {
            Log::error('ai_chat.message_failed', ['session_id' => $session->id, 'message' => $e->getMessage()]);

            return $this->json(['ok' => false, 'code' => 'error', 'message' => AiChatText::t('err_generic', [], $locale)], 500);
        } finally {
            $lock->release();
        }
    }

    /** Reloj, conexión y mensajes restantes. Lo consulta la página cada 30 s y al volver a la pestaña. */
    public function state(Request $request, string $token): JsonResponse
    {
        $session = $this->find($token);

        if (! $session) {
            return $this->json(['status' => 'invalid', 'active' => false], 404);
        }

        if (! $this->canAccess($session)) {
            return $this->json(['status' => 'auth', 'active' => false], 403);
        }

        $session = $this->sessions->refresh($session);
        $instance = (string) $request->query('instance');
        $active = $session->status === AiChatSession::STATUS_ACTIVE && $this->sessions->isActiveInstance($session, $instance);

        if ($active) {
            $this->sessions->touch($session, $instance);
        }

        return $this->json([
            'status' => $session->status,
            'active' => $active,
            'seconds_left' => $session->status === AiChatSession::STATUS_ACTIVE ? $session->secondsLeft() : 0,
            'remaining' => $this->sessions->remainingMessages($session),
            'focus' => $this->focusText($session),
        ]);
    }

    public function locale(Request $request, string $token): JsonResponse
    {
        $session = $this->find($token);

        if (! $session || ! $this->canAccess($session)) {
            return $this->json(['ok' => false], 403);
        }

        $locale = $request->input('locale');

        if (! in_array($locale, AiChatText::LOCALES, true)) {
            return $this->json(['ok' => false], 422);
        }

        $session->update(['locale' => $locale]);
        session(['ai_chat_locale' => $locale]);

        return $this->json(['ok' => true]);
    }

    /** Llega por sendBeacon al cerrar o recargar la pestaña: libera la conexión para que no cuente como un cambio. */
    public function release(Request $request, string $token): Response
    {
        $session = $this->find($token);

        if ($session) {
            $this->sessions->release($session, (string) $request->input('instance'));
        }

        return response()->noContent();
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    private function find(string $token): ?AiChatSession
    {
        if (! preg_match('/^[A-Za-z0-9]{20,64}$/', $token)) {
            return null;
        }

        return AiChatSession::with('user')->where('token_access', $token)->first();
    }

    /** Texto del enfoque que se muestra bajo el título ("Enfoque: Cardiología"), o null si aún no hay uno. */
    private function focusText(AiChatSession $session): ?string
    {
        $label = AiChatSpecialties::label($session->focus, $session->locale);

        return $label ? AiChatText::t('focus_label', ['f' => $label], $session->locale) : null;
    }

    private function accessKey(AiChatSession $session): string
    {
        return 'ai_chat_access.' . $session->id;
    }

    /** Puede entrar quien validó su identificación en este navegador o el paciente dueño con su cuenta. */
    private function canAccess(AiChatSession $session): bool
    {
        if (Auth::check() && (int) Auth::id() === (int) $session->user_id) {
            return true;
        }

        return session($this->accessKey($session)) === true;
    }

    /**
     * Estados que no entran al chat: muestran un mensaje amable.
     * Con $forStart, el estado "pendiente" sí sigue (es el que usa la vista de inicio).
     */
    private function routeByStatus(AiChatSession $session, bool $forStart = false): Response|RedirectResponse|null
    {
        $token = $session->token_access;
        $locale = $session->locale;

        return match ($session->status) {
            AiChatSession::STATUS_AWAITING_PAYMENT => redirect()->route('ai-chat.checkout', ['token' => $token]),
            AiChatSession::STATUS_EXPIRED => $this->friendly($locale, 'expired_title', 'expired_text', 'msg_buy_new', route('ai-chat.buy')),
            AiChatSession::STATUS_FINISHED => $this->friendly($locale, 'ended_title', 'ended_text', 'back_home', url('/')),
            default => null,
        };
    }

    private function payload(AiChatMessage $m): array
    {
        return ['id' => $m->id, 'role' => $m->role, 'kind' => $m->kind, 'content' => $m->content];
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)->withHeaders(['Cache-Control' => 'no-store, private']);
    }

    private function page(string $view, array $data = [], int $status = 200): Response
    {
        return response()
            ->view($view, $data, $status)
            ->withHeaders([
                'X-Robots-Tag' => 'noindex, nofollow, noarchive',
                'Cache-Control' => 'no-store, private',
                'Referrer-Policy' => 'no-referrer',
            ]);
    }

    private function friendly(string $locale, string $titleKey, string $textKey, string $ctaKey, string $ctaUrl): Response
    {
        return $this->page('ai-chat.message', [
            'locale' => $locale,
            'title' => AiChatText::t($titleKey, [], $locale),
            'text' => AiChatText::t($textKey, [], $locale),
            'ctaLabel' => AiChatText::t($ctaKey, [], $locale),
            'ctaUrl' => $ctaUrl,
        ]);
    }

    private function invalid(Request $request): Response
    {
        $locale = in_array(session('ai_chat_locale'), AiChatText::LOCALES, true)
            ? session('ai_chat_locale')
            : ($request->getPreferredLanguage(AiChatText::LOCALES) ?? 'es');

        return $this->friendly($locale, 'msg_invalid_title', 'msg_invalid_text', 'msg_buy_new', route('ai-chat.buy'));
    }
}
