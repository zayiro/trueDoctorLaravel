<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactReplyController extends Controller
{
    /**
     * Responde por correo (al instante, sin cola) a un mensaje de contacto y lo marca como respondido.
     */
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'reply' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'reply.required' => 'Escribe la respuesta antes de enviar.',
            'reply.min'      => 'La respuesta es muy corta (mínimo 5 caracteres).',
            'reply.max'      => 'La respuesta es muy larga (máximo 5000 caracteres).',
        ]);

        $contact = ContactMessage::findOrFail($id);
        $body = trim($data['reply']);

        try {
            Mail::to($contact->email)->send(new ContactReplyMail($contact, $body));
        } catch (\Throwable $e) {
            Log::error("No se pudo enviar la respuesta al mensaje de contacto #{$contact->id}: " . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'No se pudo enviar la respuesta. Revisa la conexión de correo e inténtalo de nuevo.');
        }

        try {
            $contact->forceFill([
                'replied_at' => now(),
                'replied_by' => Auth::id(),
                'reply_body' => $body,
                'is_read'    => true,
            ])->save();
        } catch (\Throwable $e) {
            Log::warning("La respuesta al mensaje #{$contact->id} se envió, pero no se pudo marcar como respondido: " . $e->getMessage());

            return back()->with('success', "Respuesta enviada a {$contact->email} (no se pudo marcar como respondida).");
        }

        return back()->with('success', "Respuesta enviada a {$contact->email}.");
    }
}
