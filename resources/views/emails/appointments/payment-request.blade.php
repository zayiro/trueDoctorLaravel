@php
    $tz = 'America/Bogota';
    $date = \Carbon\Carbon::parse($appointment->date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY');
    $time = \Carbon\Carbon::parse($appointment->start_time)->format('g:i A');
    $doctorName = $appointment->doctor?->user?->name ?? 'tu especialista';
    $patientName = $appointment->patient?->user?->name ?? 'Paciente';
    $isVirtual = ($appointment->service?->type === 'virtual') || ($appointment->address?->type === 'virtual');
@endphp
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
<tr><td align="center">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
    <tr><td style="background:#2563eb;padding:20px 28px;color:#ffffff;font-size:20px;font-weight:bold;">open<span style="color:#bfdbfe;">doctor</span></td></tr>
    <tr><td style="padding:28px;">
      <p style="margin:0 0 12px;font-size:16px;">Hola {{ $patientName }},</p>
      <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#475569;">
        Dr(a). <strong>{{ $doctorName }}</strong> te reservó una cita. Para confirmarla debes completar el pago; no necesitas iniciar sesión.
      </p>
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:20px;">
        <tr><td style="padding:16px 18px;font-size:14px;line-height:1.8;">
          <strong>Servicio:</strong> {{ $appointment->service?->name }} ({{ $isVirtual ? 'Virtual' : 'Presencial' }})<br>
          <strong>Fecha:</strong> {{ ucfirst($date) }}<br>
          <strong>Hora:</strong> {{ $time }}<br>
          <strong>Referencia:</strong> {{ $appointment->reference }}<br>
          <strong>Total a pagar:</strong> ${{ number_format($total, 0, ',', '.') }} COP
        </td></tr>
      </table>
      <p style="text-align:center;margin:0 0 20px;">
        <a href="{{ $payUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:14px 28px;border-radius:12px;">Pagar y confirmar mi cita</a>
      </p>
      @if($appointment->payment_expires_at)
        <p style="margin:0 0 12px;font-size:13px;color:#b45309;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 12px;">
          Tu turno está reservado hasta el {{ $appointment->payment_expires_at->timezone($tz)->locale('es')->isoFormat('D [de] MMMM, h:mm A') }}.
          Si no pagas antes, se libera automáticamente.
        </p>
      @endif
      <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
        Si el botón no funciona, copia este enlace en tu navegador:<br>
        <span style="word-break:break-all;">{{ $payUrl }}</span>
      </p>
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
