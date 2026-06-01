<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\User;
use App\Mail\ContactNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Notifications\MailLimitExceededNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class ContactController extends Controller
{
    public function showContact()
    {
        return view('contact');
    }

    public function showTerms()
    {
        return view('terms');
    }

    public function showPrivacy()
    {
        return view('privacy');
    }

    public function showSupport()
    {
        return view('support');
    }

    public function submit(Request $request)
    {
        // 1. Validamos los datos reales que vienen de la vista
        $validated = $request->validate([
            'name'    => 'required|string|min:3',
            'email'   => 'required|email',
            'message' => 'required|string|min:10',
        ]);

        // 2. Inyectamos un asunto por defecto de forma segura en el backend
        $validated['subject'] = $request->input('subject', 'Nuevo mensaje de contacto - SaaS');

        // 3. Guardado en la Base de Datos
        $contactRecord = ContactMessage::create($validated);

        try {
            // 4. CORREGIDO: Usamos send() en lugar de queue() para el envío inmediato en tiempo real
            //$email = config('mail.from.address', 'ocampotecnologo@gmail.com');
            $email = trim($request->email);

            Mail::to($email)->send(new ContactNotification($contactRecord));               
        } catch (Throwable $e) {
            // 1. Registramos el fallo técnico detallado en el log (storage/logs/laravel.log)
            Log::error("Fallo crítico al enviar el correo en el modulo de contactenos: " . $e->getMessage());

            // 2. Buscamos de forma segura a todos los administradores globales del sistema
            $admins = User::where('role', 'admin')->get();

            // 3. Despachamos de manera interna la notificación en la base de datos para el staff
            foreach ($admins as $admin) {
                $admin->notify(new MailLimitExceededNotification($e->getMessage(), $email));
            }
        }

        // 5. Redirección con mensaje de éxito de Bootstrap/Tailwind
        return back()->with('success', '¡Gracias! El mensaje se envió correctamente.');
    }    
}
