<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Clínico Disponible</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155;">

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        
        <!-- Encabezado con Gradiente -->
        <tr>
            <td style="background: linear-gradient(to right, #4f46e5, #7c3aed); padding: 32px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Resultados Clínicos Listos</h1>
                <p style="margin: 4px 0 0 0; color: #c7d2fe; font-size: 13px; font-weight: 500;">Análisis asistido por Inteligencia Artificial</p>
            </td>
        </tr>

        <!-- Cuerpo del Correo -->
        <tr>
            <td style="padding: 32px;">
                <p style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #0f172a;">Hola, {{ $analysis->user->name ?? 'Paciente' }}</p>
                
                <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 22px; color: #64748b;">
                    Queremos informarte que el análisis de tu examen de **"{{ $analysis->ai_result['nombre_examen'] ?? 'Laboratorio' }}"** ha sido procesado de forma exitosa tras confirmar tu pago.
                </p>

                <!-- Cuadro de Resumen IA -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; border-radius: 12px; margin-bottom: 24px;">
                    <tr>
                        <td style="padding: 20px;">
                            <h3 style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #4f46e5;">Interpretación preliminar:</h3>
                            <p style="margin: 0; font-size: 14px; line-height: 20px; color: #334155; font-style: italic;">
                                "{{ \Illuminate\Support\Str::limit($analysis->ai_result['conclusion_paciente'] ?? '', 180, '...') }}"
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Botón de Acción -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td align="center" style="padding: 10px 0 20px 0;">
                            <a href="{{ route('exams.result', $analysis->id) }}" target="_blank" style="background-color: #4f46e5; color: #ffffff; padding: 14px 28px; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 10px; display: inline-block; box-shadow: 0 4px 10px rgba(79,70,229,0.2);">
                                Ver Reporte Completo e Imprimir
                            </a>
                        </td>
                    </tr>
                </table>

                <p style="margin: 20px 0 0 0; font-size: 12px; line-height: 18px; color: #94a3b8; text-align: center;">
                    Si el botón no funciona, puedes copiar y pegar este enlace en tu navegador:<br>
                    <a href="{{ route('exams.result', $analysis->id) }}" style="color: #4f46e5; text-decoration: underline;">{{ route('exams.result', $analysis->id) }}</a>
                </p>
            </td>
        </tr>

        @if(isset($analysis->ai_result['especialidad_slug']))
        <tr>
            <td>
                <!-- BANNER DE UPSELLING: Sugerencia de Especialista -->            
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0f172a; border-radius: 12px; margin-top: 24px; border: 1px solid #1e293b;">
                    <tr>
                        <td style="padding: 24px; text-align: center;">
                            <span style="background-color: rgba(79, 70, 229, 0.2); color: #a5b4fc; padding: 4px 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px; border: 1px solid rgba(79, 70, 229, 0.3); display: inline-block; margin-bottom: 8px;">
                                Recomendación Médica
                            </span>
                            <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #ffffff; letter-spacing: -0.3px;">
                                ¿Quieres revisar esto con un especialista?
                            </h4>
                            <p style="margin: 0 0 20px 0; font-size: 13px; line-height: 18px; color: #94a3b8;">
                                La IA sugiere consultar con el área de <strong style="color: #818cf8; text-transform: capitalize;">{{ str_replace('-', ' ', $analysis->ai_result['especialidad_slug']) }}</strong> para dar un seguimiento profesional a tus métricas.
                            </p>
                            <a href="{{ route('search', ['specialty' => $analysis->ai_result['especialidad_slug']]) }}" target="_blank" style="background-color: #ffffff; color: #0f172a; padding: 12px 24px; font-size: 13px; font-weight: 700; text-decoration: none; border-radius: 8px; display: inline-block;">
                                Encontrar Especialista en OpenDoctor
                            </a>
                        </td>
                    </tr>
                </table>                
            </td>
        </tr>
        @endif

        <!-- Descargo de Responsabilidad Médica -->
        <tr>
            <td style="background-color: #fffbeb; padding: 20px 32px; border-top: 1px solid #fef3c7; border-bottom: 1px solid #fef3c7; text-align: center;">
                <p style="margin: 0; font-size: 11px; line-height: 16px; color: #b45309; font-weight: 500;">
                    <strong>Importante:</strong> Este documento es una transcripción automatizada y educativa. No reemplaza en ningún caso el diagnóstico ni el criterio de un médico especialista certificado.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 24px; background-color: #f8fafc; text-align: center;">
                <p style="margin: 0; font-size: 11px; color: #94a3b8;">&copy; {{ date('Y') }} Tu SAAS de Salud. Todos los derechos reservados.</p>
            </td>
        </tr>
    </table>

</body>
</html>
