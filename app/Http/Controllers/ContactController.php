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
            Mail::to('ocampotecnologo@gmail.com')->queue(new ContactNotification($contactRecord));
        } catch (Throwable $e) {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new MailLimitExceededNotification($e->getMessage(), $request->email));
            }
        }

        // 5. Redirección con mensaje de éxito de Bootstrap/Tailwind
        return back()->with('success', '¡Gracias! El mensaje se envió correctamente.');
    }    
}
