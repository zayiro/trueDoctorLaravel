@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Crear Landing',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        <x-form-section submit="save">
            <x-slot name="title">Nueva Campaña</x-slot>
            <x-slot name="description">Crea una página de aterrizaje personalizada.</x-slot>

            <x-slot name="form">
                <!-- Título -->
                <div class="col-span-6">
                    <x-label for="title" value="Título de la Campaña" />
                    <x-input id="title" type="text" class="mt-1 block w-full" wire:model.live="title" />
                    <x-input-error for="title" class="mt-2" />
                </div>

                <!-- Slug Preview -->
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="slug" value="URL de la Landing" />
                    <div class="flex items-center mt-1 text-sm text-gray-500">
                        <span>{{ config('app.url') }}{{ auth()->user()->slug }}/</span>
                        <span class="font-bold text-indigo-600">{{ $slug ?: 'tu-slug' }}</span>
                        <span>.html</span>
                    </div>
                    <x-input-error for="slug" class="mt-2" />
                </div>

                <!-- Contenido -->
                <div class="col-span-6">
                    <x-label for="content" value="Contenido (HTML o Texto)" />
                    <textarea id="content" wire:model="content" class="w-full border-gray-300 focus:border-indigo-500 rounded-md shadow-sm" rows="6"></textarea>
                    <x-input-error for="content" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="actions">
                <x-button>Crear Landing</x-button>
            </x-slot>
        </x-form-section>
    </div>
</x-admin-layout>