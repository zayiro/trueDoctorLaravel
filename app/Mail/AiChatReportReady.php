<?php

namespace App\Mail;

use App\Models\AiChatSession;
use App\Services\AiChat\AiChatReportService;
use App\Services\AiChat\AiChatReportText;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Correo con el informe final: resumen y plan de acción; la conversación completa solo si la persona lo pidió. */
class AiChatReportReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AiChatSession $session)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: AiChatReportText::t('mail_subject', [], $this->session->locale));
    }

    public function content(): Content
    {
        $session = $this->session->fresh() ?? $this->session;
        $locale = $session->locale;
        $report = AiChatReportService::content($session) ?? [];
        $rec = AiChatReportService::recommendation($report['specialty'] ?? null);

        $when = $session->ended_at ?? $session->end_time;
        $transcript = [];
        if ($session->email_full_copy) {
            foreach ($session->messages()->whereIn('kind', ['chat', 'quickstart'])->where('off_topic', false)->orderBy('id')->get(['role', 'content']) as $m) {
                $transcript[] = ['you' => $m->role === 'user', 'text' => $m->content];
            }
        }

        return new Content(
            view: 'emails.ai-chat-report-ready',
            with: [
                'locale' => $locale,
                'report' => $report,
                'suggestedName' => AiChatReportService::specialtyName($rec['suggested'], $locale),
                'targetName' => AiChatReportService::specialtyName($rec['target'], $locale),
                'targetSlug' => $rec['target']?->slug,
                'available' => $rec['available'],
                'hasSuggested' => (bool) $rec['suggested'],
                'transcript' => $transcript,
                'purgeDate' => $session->purge_at ? \App\Services\AiChat\AiChatReportText::longDate($session->purge_at, $locale) : '',
                'url' => route('ai-chat.report', ['token' => $session->token_access]),
                'date' => $when
                    ? $when->copy()->timezone('America/Bogota')->locale($locale)->translatedFormat($locale === 'en' ? 'F j, Y' : 'j \d\e F \d\e Y')
                    : '',
            ],
        );
    }
}
