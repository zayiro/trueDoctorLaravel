@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => auth()->user()->role == 'doctor' ? 'Partner' : 'Paciente',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    @if(auth()->user()->role == 'doctor')
        @include('admin.dashboard-partner')
    @else
        @include('admin.dashboard-patient')
    @endif
    
</x-admin-layout>