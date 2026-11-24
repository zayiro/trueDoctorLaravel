<?php

namespace App\Http\Controllers;

use App\Models\AiChatSession;
use App\Services\AiChat\AiChatSessionService;

/**
 * "Mis chats de salud con IA" en el área del paciente: sus sesiones compradas y la entrada a cada una.
 */
class AiChatPatientController extends Controller
{
    public function index(AiChatSessionService $sessions)
    {
        $userId = auth()->id();

        // Pone al día las que ya vencieron o terminaron antes de listarlas.
        AiChatSession::where('user_id', $userId)
            ->whereIn('status', [AiChatSession::STATUS_PENDING, AiChatSession::STATUS_ACTIVE])
            ->get()
            ->each(fn (AiChatSession $s) => $sessions->refresh($s));

        $rows = AiChatSession::where('user_id', $userId)
            ->whereIn('status', [
                AiChatSession::STATUS_PENDING,
                AiChatSession::STATUS_ACTIVE,
                AiChatSession::STATUS_FINISHED,
                AiChatSession::STATUS_EXPIRED,
            ])
            ->orderByDesc('paid_at')
            ->paginate(10);

        return view('patient.ai-chats.index', compact('rows'));
    }
}
