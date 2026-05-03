<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Mail\ContactNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ContactController extends Controller
{
    public function show()
    {
        return view('contact');
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|min:3',
            'email'   => 'required|email',
            'subject' => 'nullable|min:5',
            'message' => 'required|min:10',
        ]);

        // 1. Guardado en Base de Datos
        $contactRecord = ContactMessage::create($validated);

        // 2. Envío de Correo
        //Mail::to(env('MAIL_FROM_ADDRESS'))->queue(new ContactNotification($contactRecord));

        $destinatario = config('mail.from.address', 'ocampotecnologo@gmail.com'); 
        Mail::to($destinatario)->queue(new ContactNotification($contactRecord));               

        return back()->with('success', '¡Gracias! El mensaje se envió correctamente.');
    }
}
