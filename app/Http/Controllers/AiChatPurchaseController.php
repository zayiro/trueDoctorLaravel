<?php

namespace App\Http\Controllers;

use App\Enums\PaymentTransactionStatus;
use App\Models\AiChatSession;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\AiChat\AiChatPricing;
use App\Services\AiChat\AiChatSettings;
use App\Services\AiChat\AiChatText;
use App\Services\Payments\AiChatCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Compra del Chat de Salud con IA: elegir sesión → datos del paciente → pago (Wompi) → enlace por correo.
 * Páginas públicas: nunca muestran un error crudo, siempre un mensaje amable en el idioma del visitante.
 */
class AiChatPurchaseController extends Controller
{
    // ------------------------------------------------------------------
    // 1. Elegir sesión
    // ------------------------------------------------------------------

    public function buy(Request $request): Response
    {
        $locale = $this->locale($request);

        $prices = [];
        foreach (config('ai_chat.durations') as $minutes) {
            $prices[(int) $minutes] = AiChatPricing::quote((int) $minutes);
        }

        return $this->page('ai-chat.buy', [
            'locale' => $locale,
            'paused' => AiChatSettings::salesPaused(),
            'prices' => $prices,
            'days' => AiChatSettings::int('ai_chat_expiry_days'),
            'usdRate' => (int) config('ai_chat.fx_cop_per_usd'),
        ]);
    }

    public function select(Request $request): RedirectResponse
    {
        $locale = $this->locale($request);

        if (AiChatSettings::salesPaused()) {
            return redirect()->route('ai-chat.buy')->with('error', AiChatText::t('e_paused', [], $locale));
        }

        $duration = (int) $request->input('duration');

        if (! in_array($duration, config('ai_chat.durations'), true)) {
            return redirect()->route('ai-chat.buy');
        }

        session(['ai_chat_purchase' => ['duration' => $duration]]);

        return redirect()->route('ai-chat.patient');
    }

    // ------------------------------------------------------------------
    // 2. Datos del paciente
    // ------------------------------------------------------------------

    public function patient(Request $request): Response|RedirectResponse
    {
        $locale = $this->locale($request);
        $duration = (int) session('ai_chat_purchase.duration');

        if (AiChatSettings::salesPaused()) {
            return redirect()->route('ai-chat.buy')->with('error', AiChatText::t('e_paused', [], $locale));
        }

        if (! in_array($duration, config('ai_chat.durations'), true)) {
            return redirect()->route('ai-chat.buy')->with('error', AiChatText::t('e_session_lost', [], $locale));
        }

        $user = Auth::user();

        if ($user && $user->role !== 'patient') {
            return $this->message($locale, 'msg_staff_title', 'e_staff', 'msg_buy_new', route('ai-chat.buy'));
        }

        $patient = $user ? Patient::where('user_id', $user->id)->first() : null;

        if ($user && ! $patient) {
            return $this->message($locale, 'msg_profile_title', 'msg_profile_text', 'back_home', url('/'));
        }

        return $this->page('ai-chat.patient', [
            'locale' => $locale,
            'duration' => $duration,
            'quote' => AiChatPricing::quote($duration),
            'days' => AiChatSettings::int('ai_chat_expiry_days'),
            'user' => $user,
            'needsId' => $user && blank($patient->identification),
            'countryCodes' => config('ai_chat.country_codes'),
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $locale = $this->locale($request);
        $t = fn (string $key, array $r = []) => AiChatText::t($key, $r, $locale);

        if (AiChatSettings::salesPaused()) {
            return redirect()->route('ai-chat.buy')->with('error', $t('e_paused'));
        }

        $duration = (int) session('ai_chat_purchase.duration');

        if (! in_array($duration, config('ai_chat.durations'), true)) {
            return redirect()->route('ai-chat.buy')->with('error', $t('e_session_lost'));
        }

        $user = Auth::user();

        if ($user && $user->role !== 'patient') {
            return redirect()->route('ai-chat.patient')->with('error', $t('e_staff'));
        }

        $patient = $user ? Patient::where('user_id', $user->id)->first() : null;

        if ($user && ! $patient) {
            return redirect()->route('ai-chat.patient');
        }

        $hasAccount = $request->input('has_account') === 'yes';
        $registering = ! $user && ! $hasAccount;
        $needsId = $registering || ($user && blank($patient->identification));

        // ---- Validación ----
        $rules = ['terms' => ['accepted']];

        if (! $user && $hasAccount) {
            $rules['login_email'] = ['required', 'email'];
            $rules['login_password'] = ['required'];
        }

        if ($registering) {
            $rules['name'] = ['required', 'string', 'min:3', 'max:100'];
            $rules['email'] = ['required', 'email', 'max:150'];
            $rules['phone'] = ['required', 'regex:/^[0-9]{7,12}$/'];
            $rules['country_code'] = ['required', 'in:' . implode(',', array_keys(config('ai_chat.country_codes')))];
        }

        if ($needsId) {
            $rules['identification'] = ['required', 'string', 'max:40'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'terms.accepted' => $t('e_terms'),
            'login_email.required' => $t('e_email'),
            'login_email.email' => $t('e_email'),
            'login_password.required' => $t('e_login_failed'),
            'name.required' => $t('e_name'),
            'name.min' => $t('e_name'),
            'email.required' => $t('e_email'),
            'email.email' => $t('e_email'),
            'phone.required' => $t('e_phone'),
            'phone.regex' => $t('e_phone'),
            'country_code.required' => $t('e_phone'),
            'country_code.in' => $t('e_phone'),
            'identification.required' => $t('e_id_invalid'),
        ]);

        $identification = $needsId ? $this->normalizeId((string) $request->input('identification')) : null;

        $validator->after(function ($v) use ($needsId, $identification, $registering, $request, $patient, $t) {
            if ($needsId && ! $v->errors()->has('identification')) {
                if (! preg_match('/^[A-Z0-9]{4,20}$/', (string) $identification)) {
                    $v->errors()->add('identification', $t('e_id_invalid'));
                } else {
                    $taken = Patient::where('identification_blind_index', Patient::generateBlindIndex($identification))
                        ->when($patient, fn ($q) => $q->where('id', '!=', $patient->id))
                        ->exists();

                    if ($taken) {
                        $v->errors()->add('identification', $t('e_id_taken'));
                    }
                }
            }

            if ($registering && ! $v->errors()->has('email')
                && User::where('email', trim(strtolower((string) $request->input('email'))))->exists()) {
                $v->errors()->add('email', $t('e_email_taken'));
            }
        });

        if ($validator->fails()) {
            return redirect()->route('ai-chat.patient')->withErrors($validator)->withInput($request->except(['login_password']));
        }

        // ---- Cuenta: iniciar sesión o registrar ----
        if (! $user && $hasAccount) {
            $email = trim(strtolower((string) $request->input('login_email')));
            $target = User::where('email', $email)->first();

            if ($target && $target->role !== 'patient') {
                return redirect()->route('ai-chat.patient')->withErrors(['login_email' => $t('e_staff')])->withInput($request->except(['login_password']));
            }

            if (! Auth::attempt(['email' => $email, 'password' => (string) $request->input('login_password')])) {
                return redirect()->route('ai-chat.patient')->withErrors(['login_email' => $t('e_login_failed')])->withInput($request->except(['login_password']));
            }

            $request->session()->regenerate();
            $user = Auth::user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (! $patient) {
                return redirect()->route('ai-chat.patient');
            }

            // Cuenta existente sin documento guardado: se pide en este mismo paso (segunda vuelta).
            if (blank($patient->identification)) {
                return redirect()->route('ai-chat.patient');
            }
        }

        try {
            if ($registering) {
                $user = DB::transaction(function () use ($request, $identification) {
                    $newUser = User::create([
                        'name' => trim((string) $request->input('name')),
                        'email' => trim(strtolower((string) $request->input('email'))),
                        // Clave aleatoria: la persona crea la suya con el enlace del correo de bienvenida.
                        'password' => \App\Support\PasswordSetup::randomFor(trim(strtolower((string) $request->input('email')))),
                        'role' => 'patient',
                    ]);

                    $newUser->assignRole(Role::firstOrCreate(['name' => 'patient']));

                    Patient::create([
                        'user_id' => $newUser->id,
                        'identification' => $identification,
                        'phone' => $request->input('country_code') . preg_replace('/[^0-9]/', '', (string) $request->input('phone')),
                    ]);

                    return $newUser;
                });

                Auth::login($user);
                $request->session()->regenerate();
            } elseif ($needsId) {
                $patient->identification = $identification;
                $patient->save();
            }

            $quote = AiChatPricing::quote($duration);

            $session = AiChatSession::create([
                'user_id' => $user->id,
                'token_access' => AiChatSession::newToken(),
                'duration_minutes' => $duration,
                'price_cop' => $quote['base'],
                'total_cop' => $quote['total'],
                'status' => AiChatSession::STATUS_AWAITING_PAYMENT,
                'locale' => $locale,
                'terms_accepted_at' => now(),
                'terms_version' => config('ai_chat.terms_version'),
                'terms_ip' => $request->ip(),
                'consent_profile' => $request->boolean('consent_profile'),
                'email_full_copy' => $request->boolean('email_copy'),
            ]);
        } catch (Throwable $e) {
            Log::error('ai_chat.purchase_failed', ['message' => $e->getMessage()]);

            return redirect()->route('ai-chat.patient')->with('error', $t('e_generic'))->withInput($request->except(['login_password']));
        }

        session()->forget('ai_chat_purchase');

        return redirect()->route('ai-chat.checkout', ['token' => $session->token_access]);
    }

    // ------------------------------------------------------------------
    // 3. Pago
    // ------------------------------------------------------------------

    public function checkout(Request $request, string $token, AiChatCheckout $checkout): Response|RedirectResponse
    {
        $session = $this->findByToken($token);

        if (! $session) {
            return $this->invalid($this->locale($request));
        }

        $locale = $session->locale;

        if ($session->status !== AiChatSession::STATUS_AWAITING_PAYMENT) {
            return redirect()->route('ai-chat.purchased', ['token' => $token]);
        }

        if (AiChatSettings::salesPaused()) {
            return $this->message($locale, 'paused_title', 'e_paused', 'back_home', url('/'));
        }

        try {
            $payment = $checkout->start($session);
            $checkoutUrl = $checkout->checkoutUrl($payment);
        } catch (Throwable $e) {
            Log::error('ai_chat.checkout_start_failed', ['session_id' => $session->id, 'message' => $e->getMessage()]);

            return $this->message($locale, 'checkout_title', 'ck_error_start', 'ck_retry', route('ai-chat.checkout', ['token' => $token]));
        }

        return $this->page('ai-chat.checkout', [
            'locale' => $locale,
            'session' => $session,
            'checkoutUrl' => $checkoutUrl,
            'days' => AiChatSettings::int('ai_chat_expiry_days'),
            'commission' => max(0, (int) $session->total_cop - (int) $session->price_cop),
        ]);
    }

    /** Llamado desde PaymentReturnController cuando Wompi devuelve al paciente. */
    public function result(Payment $payment): RedirectResponse
    {
        $session = AiChatSession::find($payment->payable_id);

        if (! $session) {
            return redirect()->route('ai-chat.buy');
        }

        $token = $session->token_access;

        if (in_array($payment->status, [
            PaymentTransactionStatus::Declined,
            PaymentTransactionStatus::Voided,
            PaymentTransactionStatus::Error,
        ], true) && $session->status === AiChatSession::STATUS_AWAITING_PAYMENT) {
            return redirect()->route('ai-chat.checkout', ['token' => $token])
                ->with('error', AiChatText::t('purchased_declined', [], $session->locale));
        }

        return redirect()->route('ai-chat.purchased', ['token' => $token]);
    }

    // ------------------------------------------------------------------
    // 4. Después del pago
    // ------------------------------------------------------------------

    public function purchased(Request $request, string $token): Response
    {
        $session = $this->findByToken($token);

        if (! $session) {
            return $this->invalid($this->locale($request));
        }

        $locale = $session->locale;

        if (in_array($session->status, [AiChatSession::STATUS_FINISHED, AiChatSession::STATUS_EXPIRED], true)) {
            return $this->message($locale, 'purchased_other_title', 'purchased_other_text', 'msg_buy_new', route('ai-chat.buy'));
        }

        return $this->page('ai-chat.purchased', [
            'locale' => $locale,
            'session' => $session,
            'confirmed' => $session->status !== AiChatSession::STATUS_AWAITING_PAYMENT,
            'days' => AiChatSettings::int('ai_chat_expiry_days'),
        ]);
    }

    /** Enlace del correo. Provisional: el paso 3 lo reemplaza por la validación de identificación y el chat. */
    public function enter(Request $request, string $token): Response|RedirectResponse
    {
        $session = $this->findByToken($token);

        if (! $session) {
            return $this->invalid($this->locale($request));
        }

        if ($session->status === AiChatSession::STATUS_AWAITING_PAYMENT) {
            return redirect()->route('ai-chat.checkout', ['token' => $token]);
        }

        return $this->message(
            $session->locale,
            'enter_title',
            'enter_text',
            'back_home',
            url('/'),
            ['days' => AiChatSettings::int('ai_chat_expiry_days')]
        );
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    private function findByToken(string $token): ?AiChatSession
    {
        if (! preg_match('/^[A-Za-z0-9]{20,64}$/', $token)) {
            return null;
        }

        return AiChatSession::where('token_access', $token)->first();
    }

    /** Documento sin espacios, puntos ni guiones, en mayúsculas. */
    private function normalizeId(string $value): string
    {
        return strtoupper(preg_replace('/[\s.\-]/', '', trim($value)));
    }

    /** Idioma: ?lang= → el guardado en la sesión → el del navegador (español por defecto). */
    private function locale(Request $request): string
    {
        $asked = $request->query('lang');

        if (in_array($asked, AiChatText::LOCALES, true)) {
            session(['ai_chat_locale' => $asked]);

            return $asked;
        }

        $saved = session('ai_chat_locale');

        if (in_array($saved, AiChatText::LOCALES, true)) {
            return $saved;
        }

        $preferred = $request->getPreferredLanguage(AiChatText::LOCALES) ?? 'es';
        session(['ai_chat_locale' => $preferred]);

        return $preferred;
    }

    /** Respuesta con las cabeceras de privacidad: estas páginas no se indexan ni se guardan en caché. */
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

    private function message(string $locale, string $titleKey, string $textKey, string $ctaKey, string $ctaUrl, array $replace = []): Response
    {
        return $this->page('ai-chat.message', [
            'locale' => $locale,
            'title' => AiChatText::t($titleKey, $replace, $locale),
            'text' => AiChatText::t($textKey, $replace, $locale),
            'ctaLabel' => AiChatText::t($ctaKey, $replace, $locale),
            'ctaUrl' => $ctaUrl,
        ]);
    }

    private function invalid(string $locale): Response
    {
        return $this->message($locale, 'msg_invalid_title', 'msg_invalid_text', 'msg_buy_new', route('ai-chat.buy'));
    }
}
