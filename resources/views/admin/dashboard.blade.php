@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Pruebas',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    Hola mundo desde el View admin dashboard
</x-admin-layout>