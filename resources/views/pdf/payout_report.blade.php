<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Soporte de liquidación {{ $report->number }}</title>
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1f2937; }
    h1 { font-size: 18px; margin: 0 0 2px 0; }
    h2 { font-size: 12px; margin: 18px 0 6px 0; text-transform: uppercase; color: #4b5563; letter-spacing: .5px; }
    .muted { color: #6b7280; }
    .box { border: 1px solid #d1d5db; border-radius: 4px; padding: 8px 10px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; background: #f3f4f6; font-size: 10px; text-transform: uppercase; padding: 5px 6px; border-bottom: 1px solid #d1d5db; }
    td { padding: 5px 6px; border-bottom: 1px solid #e5e7eb; }
    .r { text-align: right; }
    .total td { font-weight: bold; font-size: 12px; border-top: 2px solid #1f2937; border-bottom: none; }
    .note { margin-top: 18px; font-size: 9.5px; color: #6b7280; }
</style>
</head>
<body>
    <table style="border:none;">
        <tr>
            <td style="border:none; padding:0;">
                <h1>{{ $company['name'] }}</h1>
                <div class="muted">
                    @if($company['nit']) NIT {{ $company['nit'] }} <br> @endif
                    @if($company['address']) {{ $company['address'] }}{{ $company['city'] ? ', ' . $company['city'] : '' }} <br> @endif
                    @if($company['email']) {{ $company['email'] }} @endif
                </div>
            </td>
            <td style="border:none; padding:0;" class="r">
                <div style="font-size:15px; font-weight:bold;">Soporte de liquidación</div>
                <div>N.º {{ $report->number }}</div>
                <div class="muted">Fecha de pago: {{ $report->paid_at?->timezone('America/Bogota')->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <h2>Beneficiario</h2>
    <div class="box">
        <strong>{{ $beneficiary['name'] }}</strong> ({{ $beneficiary['isClinic'] ? 'Clínica' : 'Médico' }})<br>
        @if($beneficiary['document']) {{ $beneficiary['document'] }}<br> @endif
        Referencia de la transferencia: <strong>{{ $report->transfer_reference }}</strong>
    </div>

    <h2>Detalle de citas liquidadas</h2>
    <table>
        <thead>
            <tr><th>Cita</th><th>Fecha</th><th>Modalidad</th><th>Sede</th><th class="r">Valor transferido</th></tr>
        </thead>
        <tbody>
            @foreach($lines as $l)
                <tr>
                    <td>#{{ $l['appointment'] }}</td>
                    <td>{{ $l['date'] }}</td>
                    <td>{{ $l['modality'] }}</td>
                    <td>{{ $l['place'] }}</td>
                    <td class="r">${{ number_format($l['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="4">Total transferido ({{ $report->lines_count }} cita(s))</td>
                <td class="r">${{ number_format((float) $report->total_amount, 0, ',', '.') }} COP</td>
            </tr>
        </tbody>
    </table>

    <div class="note">
        Este documento es un soporte de la transferencia realizada por {{ $company['name'] }} al beneficiario por las citas indicadas. No es una factura de venta.
        El valor transferido corresponde al valor de la consulta; la comisión de servicio la asume el paciente.
        Código de verificación: <strong>{{ $verification }}</strong>. Generado el {{ now('America/Bogota')->format('d/m/Y H:i') }}.
    </div>
</body>
</html>
