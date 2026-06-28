<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Doctor;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Bandeja de conversaciones del usuario logueado.
     */
    public function index(Request $request)
    {
        $user   = Auth::user();
        $status = $request->get('status', 'active');

        $conversations = $this->getUserConversations($user)
            ->where('status', $status)
            ->with(['patient.user', 'doctor.user', 'clinic', 'lastMessage'])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('chat.index', compact('conversations', 'status'));
    }

    /**
     * Ver una conversación específica.
     */
    public function show(Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $user = Auth::user();

        // Marcar como leídos los mensajes del otro
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = $conversation->messages()->with('sender')->get();

        $conversations = $this->getUserConversations($user)
            ->with(['patient.user', 'doctor.user', 'clinic', 'lastMessage'])
            ->orderByDesc('last_message_at')
            ->get();

        return view('chat.show', compact('conversation', 'messages', 'conversations'));
    }

    /**
     * Enviar un mensaje.
     */
    public function send(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $request->validate([
            'body'       => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|max:18432|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file           = $request->file('attachment');
            $attachmentPath = $file->store('chat/attachments', 'private');
            $attachmentName = $file->getClientOriginalName();
            $attachmentType = $file->getMimeType();
        }

        if (!$request->body && !$attachmentPath) {
            return response()->json(['error' => 'El mensaje no puede estar vacío.'], 422);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => Auth::id(),
            'body'            => $request->body,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentType,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Notificar por email si el destinatario no está en línea
        // (puedes agregar esto después)

        return response()->json([
            'message' => [
                'id'              => $message->id,
                'body'            => $message->body,
                'sender_id'       => $message->sender_id,
                'sender_name'     => Auth::user()->name,
                'attachment_name' => $message->attachment_name,
                'attachment_type' => $message->attachment_type,
                'created_at'      => $message->created_at->format('H:i'),
            ]
        ]);
    }

    /**
     * Polling — devuelve mensajes nuevos desde un ID.
     */
    public function poll(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $lastId   = $request->get('last_id', 0);
        $user     = Auth::user();

        $messages = $conversation->messages()
            ->with('sender')
            ->where('id', '>', $lastId)
            ->get()
            ->map(fn($m) => [
                'id'              => $m->id,
                'body'            => $m->body,
                'sender_id'       => $m->sender_id,
                'sender_name'     => $m->sender->name,
                'is_mine'         => $m->sender_id === $user->id,
                'attachment_name' => $m->attachment_name,
                'attachment_type' => $m->attachment_type,
                'attachment_url'  => $m->attachment_path ? route('chat.attachment', $m->id) : null,
                'created_at'      => $m->created_at->format('H:i'),
            ]);

        // Marcar como leídos
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['messages' => $messages]);
    }

    /**
     * Cambiar estado de conversación (managed, blocked).
     */
    public function updateStatus(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $request->validate([
            'status' => 'required|in:active,managed,blocked',
        ]);

        $conversation->update(['status' => $request->status]);

        // Si viene de AJAX retorna JSON, si viene de form retorna redirect
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('success', 'Estado actualizado correctamente.');
    }

    /**
     * Iniciar conversación desde perfil público.
     */
    public function start(Request $request)
    {
        $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id',
            'clinic_id' => 'nullable|exists:clinics,id',
            'body'      => 'required|string|max:2000',
        ]);

        $user    = Auth::user();
        $patient = $user->patient;

        if (!$patient) {
            return redirect()->back()->with('error', 'Solo los pacientes pueden iniciar conversaciones.');
        }

        // Buscar conversación existente
        $conversation = Conversation::where('patient_id', $patient->id)
            ->when($request->doctor_id, fn($q) => $q->where('doctor_id', $request->doctor_id))
            ->when($request->clinic_id, fn($q) => $q->where('clinic_id', $request->clinic_id))
            ->where('status', '!=', 'blocked')
            ->first();

        // Crear si no existe
        if (!$conversation) {
            $conversation = Conversation::create([
                'patient_id' => $patient->id,
                'doctor_id'  => $request->doctor_id,
                'clinic_id'  => $request->clinic_id,
                'status'     => 'active',
            ]);
        }

        // Enviar primer mensaje
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $request->body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('chat.show', $conversation)
            ->with('success', 'Mensaje enviado correctamente.');
    }

    /**
     * Descargar adjunto.
     */
    public function attachment(Message $message)
    {
        $this->authorizeConversation($message->conversation);

        return Storage::disk('private')->download(
            $message->attachment_path,
            $message->attachment_name
        );
    }

    /**
     * Query base de conversaciones según el rol del usuario.
     */
    private function getUserConversations($user)
    {
        return match ($user->role) {
            'patient' => Conversation::where('patient_id', $user->patient?->id),
            'doctor'  => Conversation::where('doctor_id', $user->doctor?->id),
            'clinic'  => Conversation::where('clinic_id', $user->clinic?->id),
            'admin'   => Conversation::query(),
            default   => Conversation::whereRaw('1=0'),
        };
    }

    /**
     * Verificar que el usuario tiene acceso a la conversación.
     */
    private function authorizeConversation(Conversation $conversation): void
    {
        $user = Auth::user();

        $hasAccess = match ($user->role) {
            'patient' => $conversation->patient_id === $user->patient?->id,
            'doctor'  => $conversation->doctor_id === $user->doctor?->id,
            'clinic'  => $conversation->clinic_id === $user->clinic?->id,
            'admin'   => true,
            default   => false,
        };

        if (!$hasAccess) {
            abort(403, 'No tienes acceso a esta conversación.');
        }
    }
}