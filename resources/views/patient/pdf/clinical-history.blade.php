<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historia Clínica - {{ $patient->user->name ?? 'Paciente' }}</title>
    <style>
        @page {
            margin: 28px 34px;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .brand {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .brand-sub {
            font-size: 9px;
            color: #64748b;
        }
        .doc-title {
            font-size: 13px;
            font-weight: bold;
            text-align: right;
            color: #1e3a8a;
            text-transform: uppercase;
        }
        .meta-right {
            font-size: 8.5px;
            text-align: right;
            color: #475569;
        }
        .section-title {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 10px;
            border-left: 4px solid #2563eb;
            margin-top: 14px;
            margin-bottom: 8px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 9.5px;
        }
        .label {
            font-weight: bold;
            color: #475569;
            display: block;
            font-size: 8px;
            text-transform: uppercase;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            background-color: #e2e8f0;
            color: #1e293b;
            text-transform: uppercase;
        }
        .badge-signed { background-color: #dcfce7; color: #166534; }
        .badge-amended { background-color: #fef3c7; color: #92400e; }
        .badge-severe { background-color: #fee2e2; color: #991b1b; }
        .history-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .history-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .soap-block {
            margin-bottom: 6px;
            padding: 5px 8px;
            background-color: #f8fafc;
            border-left: 2px solid #94a3b8;
        }
        .soap-label {
            font-weight: bold;
            font-size: 8.5px;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .notes-box {
            margin-top: 8px;
            padding: 7px 9px;
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 3px solid #d97706;
        }
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            font-size: 8px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Encabezado Oficial -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="brand">opendoctor.online</div>
                <div class="brand-sub">Plataforma de Telemedicina y Gestión Clínica • Colombia</div>
            </td>
            <td style="width: 45%;">
                <div class="doc-title">Resumen de Historia Clínica</div>
                <div class="meta-right">
                    Generado el: {{ $generationDate }}<br>
                    Solicitado por: {{ $generatedBy }}
                </div>
            </td>
        </tr>
    </table>

    <!-- 1. Identificación del Paciente -->
    <div class="section-title">1. Identificación y Datos Demográficos del Paciente</div>
    @php
        $genderLabel = match ($patient->gender) {
            'male' => 'Masculino',
            'female' => 'Femenino',
            default => 'No especificado',
        };
    @endphp
    <table class="info-table">
        <tr>
            <td style="width: 40%;">
                <span class="label">Nombre Completo</span>
                <strong>{{ $patient->user->name ?? 'No registrado' }}</strong>
            </td>
            <td style="width: 30%;">
                <span class="label">Identificación</span>
                {{ $patient->identification ?? 'N/R' }}
            </td>
            <td style="width: 30%;">
                <span class="label">Fecha Nacimiento / Edad</span>
                {{ $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date)->format('d/m/Y') : 'N/R' }}
                {{ $patient->age ? "({$patient->age} años)" : '' }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Género / Grupo Sanguíneo (RH)</span>
                {{ $genderLabel }} • <strong>{{ $patient->blood_type ?? 'N/R' }}</strong>
            </td>
            <td>
                <span class="label">Teléfono / Correo</span>
                {{ $patient->phone ?? 'N/R' }}<br>{{ $patient->user->email ?? '' }}
            </td>
            <td>
                <span class="label">Aseguradora (EPS) / Régimen</span>
                {{ $patient->insurance->name ?? 'Particular / N/R' }} ({{ $patient->regime_type ?? 'N/R' }})
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Ubicación / Zona</span>
                {{ $patient->city?->name ? "{$patient->city->name}, {$patient->department?->name}" : 'N/R' }} ({{ $patient->residence_zone ?? 'N/R' }})
            </td>
            <td>
                <span class="label">Medidas Antropométricas</span>
                Estatura: {{ $patient->height ?? 'N/R' }} • Peso: {{ $patient->weight ?? 'N/R' }}
            </td>
            <td>
                <span class="label">Contacto de Emergencia</span>
                {{ $patient->emergency_contact_name ?? 'N/R' }} {{ $patient->emergency_contact_phone ? "({$patient->emergency_contact_phone})" : '' }}
            </td>
        </tr>
    </table>
    <!-- 2. Antecedentes Reportados en el Perfil de Salud -->
    <div class="section-title">2. Antecedentes y Perfil de Salud (Reportado por el Paciente)</div>

    @if(!empty(trim($patient->permanent_conditions ?? '')))
        <table class="info-table">
            <tr>
                <td>
                    <span class="label">Condiciones Permanentes</span>
                    {{ $patient->permanent_conditions }}
                </td>
            </tr>
        </table>
    @endif

    <!-- Alergias y Medicamentos en dos columnas -->
    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <span class="label" style="margin-bottom: 4px;">Alergias Conocidas</span>
                @forelse($patient->allergies as $allergy)
                    <div style="margin-bottom: 4px;">
                        • <strong>{{ $allergy->name }}</strong> 
                        <span class="badge {{ $allergy->severity === 'severe' ? 'badge-severe' : '' }}">{{ $allergy->severity }}</span>
                        @if($allergy->reaction)
                            <br><span style="color: #64748b; font-size: 8.5px;">Reacción: {{ $allergy->reaction }}</span>
                        @endif
                    </div>
                @empty
                    <span style="color: #64748b;">Sin alergias reportadas.</span>
                @endforelse
            </td>
            <td style="width: 50%;">
                <span class="label" style="margin-bottom: 4px;">Medicamentos Actuales</span>
                @forelse($patient->medications as $medication)
                    <div style="margin-bottom: 4px;">
                        • <strong>{{ $medication->name }}</strong>
                        {{ $medication->dosage ? "({$medication->dosage})" : '' }}
                        {{ $medication->frequency ? "— {$medication->frequency}" : '' }}
                    </div>
                @empty
                    <span style="color: #64748b;">Sin medicamentos reportados.</span>
                @endforelse
            </td>
        </tr>
        <tr>
            <td>
                <span class="label" style="margin-bottom: 4px;">Cirugías Previas</span>
                @forelse($patient->surgeries as $surgery)
                    <div style="margin-bottom: 4px;">
                        • <strong>{{ $surgery->name }}</strong> ({{ $surgery->year ?? 'Año N/R' }})
                        @if($surgery->anesthesia_complications)
                            <br><span style="color: #991b1b; font-size: 8.5px;">⚠ Complicación anestésica: {{ $surgery->anesthesia_details }}</span>
                        @endif
                    </div>
                @empty
                    <span style="color: #64748b;">Sin cirugías previas reportadas.</span>
                @endforelse
            </td>
            <td>
                <span class="label" style="margin-bottom: 4px;">Antecedentes Familiares</span>
                @forelse($patient->familyHistories as $familyHistory)
                    <div style="margin-bottom: 4px;">
                        • <strong>{{ $familyHistory->condition }}</strong> ({{ $familyHistory->relationship }})
                    </div>
                @empty
                    <span style="color: #64748b;">Sin antecedentes familiares reportados.</span>
                @endforelse
            </td>
        </tr>
    </table>
    <!-- 3. Evoluciones Clínicas Oficiales (patient_histories + notes) -->
    <div class="section-title">3. Evoluciones Clínicas Oficiales y Notas Aclaratorias</div>

    @forelse($patient->histories as $history)
        @php
            $entryLabel = match ($history->entry_type) {
                'emergency'    => 'Urgencia / Prioritaria',
                'follow_up'    => 'Control / Seguimiento',
                'consultation' => 'Consulta Médica',
                default        => 'Nota Clínica',
            };
        @endphp

        <div class="history-box">
            <!-- Cabecera de la atención -->
            <table style="width: 100%; border-collapse: collapse;" class="history-header">
                <tr>
                    <td style="width: 65%; vertical-align: top;">
                        <span class="badge">{{ $entryLabel }}</span>
                        @if($history->status === 'amended')
                            <span class="badge badge-amended">Con Notas Aclaratorias</span>
                        @else
                            <span class="badge badge-signed">Firmada</span>
                        @endif
                        <div style="margin-top: 4px; font-size: 9.5px;">
                            <strong>Atendido por:</strong> Dr(a). {{ $history->doctor->user->name ?? 'Profesional Médico' }}
                        </div>
                    </td>
                    <td style="width: 35%; text-align: right; vertical-align: top; font-size: 8.5px; color: #475569;">
                        <strong>Folio:</strong> #{{ str_pad($history->id, 6, '0', STR_PAD_LEFT) }}<br>
                        <strong>Fecha:</strong> {{ $history->created_at->format('d/m/Y h:i A') }}<br>
                        @if($history->cie10_code)
                            <strong style="color: #1e3a8a;">CIE-10: {{ $history->cie10_code }}</strong>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- Secciones SOAP -->
            <div class="soap-block">
                <div class="soap-label">S • Subjetivo (Motivo y Síntomas)</div>
                <div>{!! nl2br(e($history->soap_subjective ?: 'Sin registros en esta sección.')) !!}</div>
            </div>

            <div class="soap-block">
                <div class="soap-label">O • Objetivo (Examen Físico y Hallazgos)</div>
                <div>{!! nl2br(e($history->soap_objective ?: 'Sin registros en esta sección.')) !!}</div>
            </div>

            <div class="soap-block">
                <div class="soap-label">A • Análisis y Diagnóstico</div>
                <div>{!! nl2br(e($history->soap_assessment ?: 'Sin registros en esta sección.')) !!}</div>
            </div>

            <div class="soap-block" style="margin-bottom: 0;">
                <div class="soap-label">P • Plan de Tratamiento y Recomendaciones</div>
                <div>{!! nl2br(e($history->soap_plan ?: 'Sin registros en esta sección.')) !!}</div>
            </div>

            <!-- Notas Extemporáneas / Aclaratorias (si existen) -->
            @if($history->notes && $history->notes->isNotEmpty())
                <div class="notes-box">
                    <div style="font-size: 8.5px; font-weight: bold; color: #92400e; text-transform: uppercase; margin-bottom: 5px;">
                        Notas Extemporáneas / Aclaratorias ({{ $history->notes->count() }}/3)
                    </div>

                    @foreach($history->notes as $idx => $note)
                        <div style="margin-bottom: 5px; padding-bottom: 4px; border-bottom: 1px dashed #fcd34d;">
                            <div style="font-size: 8.5px; font-weight: bold; color: #78350f;">
                                Nota #{{ $idx + 1 }} — Dr(a). {{ $note->doctor->user->name ?? 'Profesional Médico' }} ({{ $note->created_at->format('d/m/Y h:i A') }})
                            </div>
                            <div style="font-size: 9.5px; color: #1e293b; margin-top: 2px;">
                                {!! nl2br(e($note->content)) !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div style="padding: 12px; border: 1px dashed #cbd5e1; text-align: center; color: #64748b;">
            No existen evoluciones médicas oficiales firmadas hasta la fecha de generación de este documento.
        </div>
    @endforelse

    <!-- Pie Legal de Cumplimiento en Colombia -->
    <div class="footer">
        Documento confidencial generado electrónicamente a través de <strong>opendoctor.online</strong> conforme a la normativa vigente de Historia Clínica Electrónica en Colombia (Resolución 1995 de 1999 y Ley 2015 de 2020).<br>
        Los registros clínicos firmados son inmutables y cualquier adición posterior se anexa cronológicamente como nota aclaratoria.
    </div>

</body>
</html>
