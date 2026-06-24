@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Directorio de Pacientes',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="p-6 bg-white rounded-lg shadow">
        {{-- Cabecera --}}
        <div class="header">
            <h1>Receta Médica Electrónica</h1>
            <p><strong>opendoctor.online</strong> — Plataforma de Salud Digital</p>
            <p>Fecha de expedición: {{ $issued_at }}</p>
        </div>

        {{-- Datos del médico --}}
        <div class="section-title">Médico tratante</div>
        <p><strong>{{ $doctor->gender === 'female' ? 'Dra.' : 'Dr.' }} {{ $doctor->user->name }}</strong></p>
        <p>Tarjeta profesional: {{ $doctor->medical_license }}</p>
        <p>Especialidad: {{ $doctor->specialties->first()?->name ?? 'Médico General' }}</p>

        {{-- Datos del paciente --}}
        <div class="section-title">Paciente</div>
        <p><strong>{{ $patient->user->name }}</strong></p>
        <p>Documento: {{ $patient->identification }}</p>

        {{-- Medicamentos --}}
        <div class="section-title">Medicamentos prescritos</div>
        @foreach($prescription->items as $item)
        <div class="drug-row">
            <div class="drug-name">{{ $item->drug_name }} {{ $item->dosage }}</div>
            <div>{{ $item->frequency }} — {{ $item->duration }}</div>
            @if($item->instructions)
            <div style="color:#555; margin-top:4px">{{ $item->instructions }}</div>
            @endif
        </div>
        @endforeach

        {{-- Diagnóstico --}}
        @if($prescription->diagnosis)
        <div class="section-title">Diagnóstico / CIE-10</div>
        <p>{{ $prescription->diagnosis }}</p>
        @endif

        {{-- Bloque de firma digital simple --}}
        <div class="signature-block">
            <span class="valid-badge">✓ Documento con firma digital simple</span>
            <table>
                <tr>
                    <td><strong>Firmado por:</strong> {{ $doctor->user->name }}</td>
                    <td><strong>Fecha y hora:</strong> {{ $signed_at }}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Tarjeta profesional:</strong> {{ $doctor->medical_license }}</td>
                </tr>
            </table>
            <div class="hash-box">
                <strong>Hash del documento (SHA-256):</strong><br>
                {{ $document_hash }}<br><br>
                <strong>Firma HMAC-SHA-256:</strong><br>
                {{ $signature_hash }}
            </div>
            <p style="font-size:9px; color:#94a3b8; margin-top:8px">
                Este documento cuenta con firma digital simple según la Ley 527 de 1999.
                Verifique su autenticidad en opendoctor.online/verify/{{ $signature_hash }}
            </p>
        </div>
    </div>
</x-admin-layout>