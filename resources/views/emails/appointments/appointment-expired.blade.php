@component('mail::message')
# Tu reserva ha expirado

Hola **{{ $appointment->patient->user->name ?? 'paciente' }}**,

Lamentamos informarte que tu reserva de cita médica **ha expirado** porque el pago no fue completado dentro del tiempo límite de 2 horas.

---

**Cita que expiró:**
- 👨‍⚕️ **Doctor:** Dr(a). {{ $appointment->doctor->user->name ?? 'Especialista' }}
- 📅 **Fecha:** {{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}
- 🕐 **Hora:** {{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }}
- 🏥 **Servicio:** {{ $appointment->service->name ?? 'Consulta virtual' }}

---

¿Deseas agendar de nuevo? El especialista puede seguir disponible.

@component('mail::button', ['url' => $bookAgainUrl, 'color' => 'blue'])
Agendar nueva cita
@endcomponent

Si crees que esto fue un error o tuviste problemas con el pago, escríbenos por WhatsApp y con gusto te ayudamos.

Gracias,
**El Equipo de {{ config('app.name') }}**
@endcomponent