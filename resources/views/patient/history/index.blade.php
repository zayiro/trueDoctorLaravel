@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Registros Clínicos',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-6 sm:py-10 px-4 sm:px-6 lg:px-8">
        
        {{-- Banner de alertas de éxito o error --}}
        @if (session('success'))
            <div class="w-full text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-4 py-3 rounded-xl mb-4">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div class="ms-3 text-sm font-bold">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        {{-- Encabezado y Acción Principal responsivos --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">Registros Clínicos</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Diagnósticos médicos y reportes previos adjuntados.</p>
            </div>

            <!-- Controlador Alpine para el Modal de Subida -->
            <div x-data="{ openUploadModal: false }" class="w-full sm:w-auto">
                <button @click="openUploadModal = true" 
                        type="button" 
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:bg-blue-500 dark:hover:bg-blue-600">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l4.5-4.5a3 3 0 114.243 4.243l-4.5 4.5a1.5 1.5 0 11-2.122-2.122l4.5-4.5" />
                    </svg>
                    <span>Anexar registro clínico</span>
                </button>
                <!-- Fondo Opaco (Backdrop) y Estructura del Modal -->
                <div x-show="openUploadModal" 
                    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-6"
                    x-keydown.escape.window="openUploadModal = false"
                    style="display: none;">
                    
                    <!-- Fondo oscuro difuminado -->
                    <div x-show="openUploadModal"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"
                        @click="openUploadModal = false"></div>

                    <!-- Contenedor del Modal -->
                    <div x-show="openUploadModal"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-5 sm:p-6 text-left shadow-xl transition-all dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                        
                        <!-- Cabecera -->
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>📁</span> Subir Reporte Médico Anterior
                            </h3>
                            <button @click="openUploadModal = false" type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Formulario y Área Drag and Drop -->
                        <form action="{{ route('patient.attachments.store') }}" method="POST" enctype="multipart/form-data" x-data="{ files: [], dragging: false }">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nombre o descripción corta del reporte</label>
                                    <input type="text" name="name" required placeholder="Ej: Exámenes de Sangre - Laboratorio XYZ" 
                                        class="w-full text-sm rounded-lg border-slate-200 bg-white p-2.5 text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">
                                </div>

                                <!-- Dropzone Nativo de Archivos -->
                                <div :class="{ 'border-blue-500 bg-blue-50/40 dark:bg-blue-950/20': dragging, 'border-slate-300 dark:border-slate-700': !dragging }"
                                    @dragover.prevent="dragging = true"
                                    @dragleave.prevent="dragging = false"
                                    @drop.prevent="dragging = false; files = [...$event.dataTransfer.files]; $refs.fileInput.files = $event.dataTransfer.files"
                                    class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-5 sm:p-6 text-center transition-all">
                                    
                                    <svg class="h-10 w-10 text-slate-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                    </svg>

                                    <div class="text-sm text-slate-600 dark:text-slate-400">
                                        <label class="relative cursor-pointer rounded-md font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                            <span>Selecciona un archivo</span>
                                            <input x-ref="fileInput" type="file" name="file" required accept="image/*,application/pdf" class="sr-only"
                                                @change="files = [...$event.target.files]">
                                        </label>
                                        o arrástralo aquí
                                    </div>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Formatos admitidos: PDF, JPG, PNG o WEBP (Máx. 10MB)</p>
                                </div>

                                <!-- Visualizador Dinámico de Archivo Cargado -->
                                <template x-if="files.length > 0">
                                    <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2 min-w-0 text-slate-700 dark:text-slate-300">
                                            <span x-text="files[0].type.includes('pdf') ? '📄' : '🖼️'"></span>
                                            <span class="truncate font-medium" x-text="files[0].name"></span>
                                            <span class="text-slate-400 shrink-0" x-text="`(${(files[0].size / 1024 / 1024).toFixed(2)} MB)`"></span>
                                        </div>
                                        <button type="button" @click="files = []; $refs.fileInput.value = ''" class="text-red-500 hover:text-red-700 ml-2 shrink-0">Remover</button>
                                    </div>
                                </template>

                                <!-- Acciones del Formulario -->
                                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 border-t border-slate-100 dark:border-slate-800 pt-4 mt-6">
                                    <button @click="openUploadModal = false" type="button" 
                                            class="w-full sm:w-auto rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                        Cancelar
                                    </button>
                                    <button type="submit" 
                                            class="w-full sm:w-auto rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                                        Subir Adjunto
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contenedor Alpine del Visor y Listado en Grid -->
        <div x-data="{ previewModal: false, previewUrl: '', fileType: '' }" class="w-full mt-4">
            
            @if($patient->attachments->isEmpty())
                <div class="w-full rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Aún no has anexado reportes o exámenes médicos.</p>
                </div>
            @else
                <!-- Grid Responsivo: 1 columna al 100% en móvil, 2 en tablet, 3 en escritorio -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 w-full">
                    @foreach($patient->attachments as $attachment)
                        <div class="group relative flex items-center justify-between gap-3 w-full p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-800 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-all shadow-sm">
                
                            <!-- Contenedor Clickable para Previsualizar (alineado horizontalmente en móvil para aprovechar el ancho) -->                
                            <div class="cursor-pointer flex items-center gap-3.5 flex-1 min-w-0"
                                data-url="{{ route('patient.attachments.view', $attachment) }}"
                                data-type="{{ $attachment->file_type }}"
                                @click="previewUrl = $el.getAttribute('data-url'); fileType = $el.getAttribute('data-type'); previewModal = true">
                                
                                <!-- Iconografía Emoticon/SVG -->
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 group-hover:bg-blue-50 dark:group-hover:bg-blue-950/30 transition-colors">
                                    @if(str_contains($attachment->file_type, 'pdf'))
                                        <span class="text-xl">📄</span>
                                    @else
                                        <span class="text-xl">🖼️</span>
                                    @endif
                                </div>

                                <!-- Textos del Documento con min-w-0 para que truncate funcione sin desbordar -->
                                <div class="min-w-0 flex-1 text-left">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate group-hover:text-blue-600 transition-colors">
                                        {{ $attachment->name ?? 'Documento sin nombre' }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                        {{ number_format($attachment->file_size / 1024 / 1024, 2) }} MB • <span class="text-blue-600 dark:text-blue-400 font-medium">Ver reporte</span>
                                    </p>
                                    <span class="block text-[11px] text-slate-400 mt-0.5">
                                        {{ $attachment->created_at->translatedFormat('d \d\e F \d\e Y') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Formulario Seguro de Eliminación con Confirmación Alpine.js -->
                            <form action="{{ route('patient.attachments.destroy', $attachment) }}" 
                                method="POST"
                                class="shrink-0"
                                @submit.prevent="if (confirm('¿Estás seguro de que deseas eliminar este reporte médico? Esta acción no se puede deshacer.')) $el.submit()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all dark:hover:bg-red-950/30 dark:hover:text-red-400"
                                        title="Eliminar reporte">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>

                        </div>
                    @endforeach
                </div>
            @endif

            <!-- MODAL DE PREVISUALIZACIÓN EXPRESS (Alpine.js) -->
            <div x-show="previewModal" 
                class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4"
                x-keydown.escape.window="previewModal = false; previewUrl = ''"
                style="display: none;">

                <!-- Backdrop -->
                <div x-show="previewModal" 
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" 
                    @click="previewModal = false; previewUrl = ''"></div>

                <!-- Contenedor del Visor -->
                <div x-show="previewModal"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    style="height: 90vh; max-height: 90vh;"
                    class="relative z-10 w-full max-w-6xl overflow-hidden rounded-2xl bg-white p-3 sm:p-4 shadow-2xl transition-all dark:bg-slate-900 flex flex-col">
                    
                    <!-- Cabecera del Visor -->
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3 shrink-0">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Vista Previa del Documento Médico</h3>
                        <button @click="previewModal = false; previewUrl = ''" type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            ✕
                        </button>
                    </div>

                    <!-- Área del Contenido Dinámico -->
                    <div class="flex-1 w-full bg-slate-50 dark:bg-slate-950 rounded-xl overflow-hidden flex items-center justify-center relative" style="min-height: 0;">
                        <template x-if="fileType.includes('pdf')">
                            <iframe :src="previewUrl" class="w-full h-full border-0" style="min-height: 100%;" allow="autoplay"></iframe>
                        </template>

                        <template x-if="!fileType.includes('pdf')">
                            <img :src="previewUrl" class="max-w-full max-h-full object-contain p-2">
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
