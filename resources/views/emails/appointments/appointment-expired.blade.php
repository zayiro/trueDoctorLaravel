@php
    $channel = $appointment->channel instanceof \BackedEnum ? $appointment->channel->value : (string) $appointment->channel;
    $byDoctor = $channel === 'doctor';
@endphp
@component('mail::message')
# Tu reserva ha expirado

Hola **{{ $appointment->patient->user->name ?? 'paciente' }}**,

@if ($byDoctor)
Lamentamos informarte que la reserva que tu especialista agendó para ti **ha expirado**: el pago no se completó antes de que venciera el enlace de pago y el horario fue liberado.
@else
Lamentamos informarte que tu reserva de cita médica **ha expirado** porque el pago no fue completado dentro del tiempo límite.
@endif

---

**Cita que expiró:**
- 👨‍⚕️ **Doctor:** Dr(a). {{ $appointment->doctor->user->name ?? 'Especialista' }}
- 📅 **Fecha:** {{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}
- 🕐 **Hora:** {{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }}
- 🏥 **Servicio:** {{ $appointment->service->name ?? 'Consulta virtual' }}

---

@if ($byDoctor)
Si aún quieres la cita, comunícate con tu especialista para que te reserve un nuevo horario y te envíe otro enlace de pago.
@else
¿Deseas agendar de nuevo? El especialista puede seguir disponible.

@component('mail::button', ['url' => $bookAgainUrl, 'color' => 'blue'])
Agendar nueva cita
@endcomponent
@endif

Si crees que esto fue un error o tuviste problemas con el pago, escríbenos por WhatsApp y con gusto te ayudamos.

Gracias,
**El Equipo de {{ config('app.name') }}**
@endcomponent
