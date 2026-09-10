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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

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

    public function showAbout()
    {
        return view('about');
    }

    public function submit(Request $request)
    {
        // Rate limiting por IP
        if (RateLimiter::tooManyAttempts('contact-form:'.$request->ip(), 5)) {
            return back()->withErrors(['error' => 'Demasiados intentos. Intenta de nuevo en 1 hora.']);
        }

        // Honeypot: detecta bots
        if ($request->filled('website')) {
            return back()->with('success', '¡Gracias! El mensaje se envió correctamente.');
        }

        // 1. Validamos los datos reales que vienen de la vista
        $validated = $request->validate([
            'name'    => 'required|string|min:3',
            'email'   => 'required|email',
            'message' => 'required|string|min:10',
        ]);
        
        // Validar honeypot (si usás spatie/laravel-honeypot)
        $validated = $request->validate([
            'name'    => 'required|string|min:3|max:100',
            'email'   => 'required|email:rfc,dns|max:100',
            'message' => 'required|string|min:10|max:150',            
            'email_address' => 'honeypot', // Campo trampa del honeypot
            'website' => 'honeypot', // Otro campo trampa
        ]);

        // 2. Inyectamos un asunto por defecto de forma segura en el backend
        $validated['subject'] = $request->input('subject', 'Nuevo mensaje de contacto - SaaS');

        // 3. Guardado en la Base de Datos
        $contactRecord = ContactMessage::create($validated);

        try {
            $supportEmail = config('services.mail_site.support');
            
            if (!$supportEmail) {
                throw new \Exception('Support email not configured');
            }
            
            Mail::to($supportEmail)->send(new ContactNotification($contactRecord));            
            
        } catch (Throwable $e) {                       
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new MailLimitExceededNotification($e->getMessage(), $request->email));
            }
        }

        // Registrar el rate limit hit
        RateLimiter::hit('contact-form:'.$request->ip());

        // 5. Redirección con mensaje de éxito de Bootstrap/Tailwind
        return back()->with('success', '¡Gracias! El mensaje se envió correctamente.');
    }  
    
    public function storeAvailabilityNotify(Request $request): JsonResponse
    {
        $request->validate([
            'email'     => ['required', 'email', 'max:255'],
            'specialty' => ['required', 'string', 'max:255'],
        ]);

        $name = 'symptom';
        $email = $request->input('email');
        $subject = 'Avísame cuando haya disponibilidad';
        $message = $request->input('message', 'No se encuentra especialista en busqueda por symptom');

        // 3. Guardado en la Base de Datos
        $contactRecord = ContactMessage::create([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message
        ]);
        
        try {
            $supportEmail = config('services.mail_site.support');
            
            if (!$supportEmail) {
                throw new \Exception('Support email not configured');
            }
            
            Mail::to($supportEmail)->send(new ContactNotification($contactRecord));            
            
        } catch (Throwable $e) {                       
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new MailLimitExceededNotification($e->getMessage(), $request->email));
            }
        }

        return response()->json([
            'message' => 'Solicitud registrada correctamente.',
        ], 200);
    }
}
