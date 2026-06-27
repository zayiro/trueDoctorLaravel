// resources/views/emails/appointments/payment-failed.blade.php
@component('mail::message')
# ⏳ Tu cita está reservada

Hola **{{ $appointment->patient->user->name ?? 'paciente' }}**,

Notamos que tu pago no se completó, pero no te preocupes — **tu cita sigue reservada** hasta las **{{ $expiresAt }}**.

---

**Resumen de tu cita:**
- 👨‍⚕️ **Doctor:** Dr(a). {{ $appointment->doctor->user->name ?? 'Especialista' }}
- 📅 **Fecha:** {{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}
- 🕐 **Hora:** {{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }}
- 💰 **Total:** ${{ number_format($appointment->price + $appointment->commission_amount, 0, ',', '.') }} COP

@component('mail::button', ['url' => $retryUrl, 'color' => 'blue'])
Completar mi pago ahora
@endcomponent

Si tienes problemas con el pago, responde este correo o escríbenos por WhatsApp.

Gracias,
**El Equipo de {{ config('app.name') }}**
@endcomponent