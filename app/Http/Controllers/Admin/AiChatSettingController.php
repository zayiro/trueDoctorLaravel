<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiChatSession;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiChatSettingController extends Controller
{
    /** Panel "Chat de Salud con IA": precios base, límites, modelos y pausa de ventas (la comisión de Wompi se configura en Configuraciones). */
    public function index(): View
    {
        $stored = Setting::where('group', 'ai_chat')->pluck('value', 'key')->all();

        return view('administrator.ai-chat.settings', [
            'values' => array_merge(config('ai_chat.defaults'), $stored),
            'models' => config('ai_chat.models'),
            'stats' => AiChatSession::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
        ]);
    }

    /** Valida y guarda solo los ajustes del chat (no toca los demás ajustes del sistema). */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) $value, 'group' => 'ai_chat']
                );
            }
        });

        return redirect()->route('administrator.ai-chat.index')
            ->with('success', '✅ Configuración del Chat de Salud con IA guardada. Aplica a las sesiones nuevas.');
    }

    private function rules(): array
    {
        $model = ['required', Rule::in(array_keys(config('ai_chat.models')))];

        return [
            'ai_chat_price_20' => ['required', 'integer', 'min:3000', 'max:500000'],
            'ai_chat_price_30' => ['required', 'integer', 'min:3000', 'max:500000'],
            'ai_chat_max_messages_20' => ['required', 'integer', 'min:5', 'max:200'],
            'ai_chat_max_messages_30' => ['required', 'integer', 'min:5', 'max:200'],
            'ai_chat_max_chars' => ['required', 'integer', 'min:100', 'max:4000'],
            'ai_chat_max_reply_words' => ['required', 'integer', 'min:50', 'max:1000'],
            'ai_chat_offtopic_limit' => ['required', 'integer', 'min:1', 'max:10'],
            'ai_chat_expiry_days' => ['required', 'integer', 'min:1', 'max:60'],
            'ai_chat_retention_days' => ['required', 'integer', 'min:7', 'max:365'],
            'ai_chat_nudge_seconds' => ['required', 'integer', 'min:30', 'max:600'],
            'ai_chat_id_max_attempts' => ['required', 'integer', 'min:3', 'max:10'],
            'ai_chat_sales_paused' => ['required', Rule::in(['0', '1'])],
            'ai_chat_model_chat' => $model,
            'ai_chat_model_report' => $model,
            'ai_chat_model_fallback' => $model,
        ];
    }
}
