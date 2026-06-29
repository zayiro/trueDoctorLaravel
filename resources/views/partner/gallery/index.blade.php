@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Galeria de imagenes',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-5xl mx-auto px-4 py-8">

        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-slate-800">Galería de Fotos</h2>
                <p class="text-sm text-slate-500">Sube fotos de tu sede o consultorio. Se mostrarán en tu perfil público.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 mb-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 mb-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        {{-- ZONA DE CARGA --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6"
            x-data="{
                dragging: false,
                previews: [],
                files: [],
                handleFiles(newFiles) {
                    Array.from(newFiles).forEach(file => {
                        if (!file.type.startsWith('image/')) return;
                        const reader = new FileReader();
                        reader.onload = e => this.previews.push({ url: e.target.result, name: file.name });
                        reader.readAsDataURL(file);
                        this.files.push(file);
                    });
                },
                removePreview(index) {
                    this.previews.splice(index, 1);
                    this.files.splice(index, 1);
                },
                async submit() {
                    if (this.files.length === 0) return;
                    this.$refs.form.submit();
                }
            }">

            <form action="{{ route('partner.gallery.store') }}" method="POST"
                enctype="multipart/form-data" x-ref="form">
                @csrf

                {{-- Dropzone --}}
                <div
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; handleFiles($event.dataTransfer.files)"
                    @click="$refs.fileInput.click()"
                    :class="dragging ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:border-blue-300 hover:bg-slate-50'"
                    class="border-2 border-dashed rounded-2xl p-10 text-center cursor-pointer transition">

                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                    </svg>

                    <p class="text-sm font-bold text-slate-500">Arrastra tus fotos aquí o <span class="text-blue-600">haz clic para seleccionar</span></p>
                    <p class="text-xs text-slate-400 mt-1">JPG, PNG, WEBP — Máx. 5MB por imagen — Hasta 10 imágenes</p>

                    <input type="file" name="images[]" multiple accept="image/*"
                        class="hidden" x-ref="fileInput"
                        @change="handleFiles($event.target.files)">
                </div>

                {{-- Previews --}}
                <div x-show="previews.length > 0" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3" style="display:none;">
                    <template x-for="(preview, index) in previews" :key="index">
                        <div class="relative group rounded-xl overflow-hidden aspect-square bg-slate-100">
                            <img :src="preview.url" class="w-full h-full object-cover">
                            <button type="button" @click.stop="removePreview(index)"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <p class="absolute bottom-0 left-0 right-0 bg-black/40 text-white text-[10px] px-2 py-1 truncate" x-text="preview.name"></p>
                        </div>
                    </template>
                </div>

                {{-- Botón subir --}}
                <div x-show="previews.length > 0" class="mt-4 flex justify-end" style="display:none;">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition">
                        Subir <span x-text="previews.length"></span> imagen(es)
                    </button>
                </div>
            </form>
        </div>

        {{-- GALERÍA ACTUAL --}}
        @if($images->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-sm font-black text-slate-700 uppercase tracking-wider mb-4">
                    Tus fotos ({{ $images->count() }})
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
                    x-data="{
                        async reorder(order) {
                            await fetch('{{ route('partner.gallery.reorder') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ order })
                            });
                        }
                    }">

                    @foreach($images as $image)
                        <div class="group relative rounded-2xl overflow-hidden aspect-square bg-slate-100 shadow-sm"
                            x-data="{ editing: false, caption: '{{ addslashes($image->caption ?? '') }}' }">

                            <img src="{{ $image->url }}" class="w-full h-full object-cover transition group-hover:scale-105 duration-300">

                            {{-- Overlay acciones --}}
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex flex-col justify-between p-3">

                                {{-- Botón eliminar --}}
                                <div class="flex justify-end">
                                    <form action="{{ route('partner.gallery.destroy', $image) }}" method="POST"
                                        onsubmit="return confirm('¿Eliminar esta foto?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>

                                {{-- Caption --}}
                                <div>
                                    <template x-if="!editing">
                                        <p @click="editing = true"
                                            class="text-white text-xs font-medium truncate cursor-pointer"
                                            x-text="caption || 'Agregar descripción...'"></p>
                                    </template>
                                    <template x-if="editing">
                                        <input type="text" x-model="caption" maxlength="150"
                                            placeholder="Descripción de la foto..."
                                            @blur="
                                                editing = false;
                                                fetch('{{ route('partner.gallery.update', $image) }}', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                    },
                                                    body: JSON.stringify({ _method: 'PATCH', caption: caption })
                                                })
                                            "
                                            @keydown.enter="$el.blur()"
                                            class="w-full bg-white/20 text-white text-xs rounded-lg px-2 py-1 border border-white/30 focus:outline-none placeholder-white/60"
                                            x-ref="captionInput"
                                            x-init="$nextTick(() => $refs.captionInput?.focus())">
                                    </template>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                </svg>
                <p class="text-sm font-medium">Aún no has subido ninguna foto</p>
                <p class="text-xs mt-1">Sube fotos de tu sede para que los pacientes conozcan tu consultorio</p>
            </div>
        @endif
    </div>
</x-admin-layout>