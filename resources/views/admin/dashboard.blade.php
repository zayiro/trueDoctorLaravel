@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Consola Analítica de Negocio']
];

$validation_status = auth()->user()->doctor->validation_status;
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <!-- Contenedor Centralizador de Consolas Analíticas -->
    @if(auth()->user()->role == 'doctor')
        <!-- Despacha las métricas del médico independiente -->
        @include('admin.dashboard-partner')
        
    @elseif (auth()->user()->role == 'clinic')
        <!-- Despacha las métricas corporativas de la clínica -->
        @include('admin.dashboard-clinic')
        
    @elseif (auth()->user()->role == 'admin')
        <!-- Panel del superadministrador global del SaaS -->
        @include('admin.dashboard-administrator')
        
    @else
        <!-- Panel básico para pacientes de opendoctor.online -->
        @include('admin.dashboard-patient')        
    @endif
</x-admin-layout>
