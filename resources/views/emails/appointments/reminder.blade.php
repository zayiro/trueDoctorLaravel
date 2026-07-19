@component('mail::message')
# ⏰ Recordatorio de tu cita médica

Hola **{{ $appointment->patient->user->name ?? 'paciente' }}**,

Te recordamos que tienes una cita médica programada. ¡No olvides asistir!

---

**Detalles de tu cita:**
- 👨‍⚕️ **Doctor:** Dr(a). {{ $doctor }}
- 📅 **Fecha:** {{ $date }}
- 🕐 **Hora:** {{ $time }}
- 🏥 **Servicio:** {{ $appointment->service->name ?? 'Consulta médica' }}

---

@if($appointment->service?->type === 'virtual')
Tu cita es **virtual**. El enlace de videollamada estará disponible en tu dashboard minutos antes de la cita.

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'blue'])
Ver mi cita
@endcomponent

@else
Recuerda llegar **15 minutos antes** de tu cita.

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'blue'])
Ver detalles
@endcomponent
@endif

Si necesitas cancelar o reagendar, hazlo con anticipación.

Gracias,
**El Equipo de {{ config('app.name') }}**
@endcomponent