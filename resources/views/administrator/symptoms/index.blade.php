@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Url dinamicas',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div x-data="symptomsAdmin()" class="min-h-screen bg-[#F4F1EA] px-4 py-8 md:px-8" style="font-family: 'Inter', sans-serif;">

        {{-- Encabezado --}}
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-semibold text-teal-900" style="font-family: 'Fraunces', serif;">
                    Síntomas indexados
                </h1>
                <p class="mt-1 text-sm text-teal-700/70">
                    Búsquedas de pacientes, nivel de urgencia y derivación médica sugerida.
                </p>
            </div>
            <button
                @click="openCreate()"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#E8735C] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#d9614a] focus:outline-none focus:ring-2 focus:ring-[#E8735C] focus:ring-offset-2"
            >
                + Nuevo síntoma
            </button>
        </div>

        {{-- Mensajes flash --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Stats --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-teal-900/10 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-teal-700/60">Total registrados</p>
                <p class="mt-1 text-2xl font-semibold text-teal-900" style="font-family: 'IBM Plex Mono', monospace;">
                    {{ $stats['total'] }}
                </p>
            </div>
            <div class="rounded-xl border border-[#E8735C]/20 bg-[#FBEAE6] p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-[#b3452f]">Derivación inmediata</p>
                <p class="mt-1 text-2xl font-semibold text-[#b3452f]" style="font-family: 'IBM Plex Mono', monospace;">
                    {{ $stats['urgentes'] }}
                </p>
            </div>
            <div class="rounded-xl border border-teal-900/10 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-teal-700/60">Sin especialidad asignada</p>
                <p class="mt-1 text-2xl font-semibold text-teal-900" style="font-family: 'IBM Plex Mono', monospace;">
                    {{ $stats['sin_especialidad'] }}
                </p>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" class="mb-6 flex flex-col gap-3 rounded-xl border border-teal-900/10 bg-white p-4 shadow-sm sm:flex-row sm:flex-wrap sm:items-end">
            <div class="w-full sm:flex-1 sm:min-w-[200px]">
                <label class="mb-1 block text-xs font-medium text-teal-700/70">Buscar</label>
                <input
                    type="text" name="q" value="{{ request('q') }}"
                    placeholder="Síntoma o título SEO..."
                    class="w-full rounded-lg border-teal-900/15 text-sm focus:border-[#E8735C] focus:ring-[#E8735C]"
                >
            </div>

            <div class="grid grid-cols-2 gap-3 sm:contents">
                <div>
                    <label class="mb-1 block text-xs font-medium text-teal-700/70">Urgencia</label>
                    <select name="urgency_level" class="w-full rounded-lg border-teal-900/15 text-sm focus:border-[#E8735C] focus:ring-[#E8735C] sm:w-auto">
                        <option value="">Todas</option>
                        @foreach (\App\Models\IndexedSymptom::NIVELES_URGENCIA as $nivel)
                            <option value="{{ $nivel }}" @selected(request('urgency_level') === $nivel)>{{ $nivel }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-teal-700/70">Especialidad</label>
                    <select name="specialty_id" class="w-full rounded-lg border-teal-900/15 text-sm focus:border-[#E8735C] focus:ring-[#E8735C] sm:w-auto">
                        <option value="">Todas</option>
                        @foreach ($specialties as $specialty)
                            <option value="{{ $specialty->id }}" @selected((string) request('specialty_id') === (string) $specialty->id)>
                                {{ $specialty->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label class="mb-1 flex items-center gap-2 text-sm text-teal-800">
                <input
                    type="checkbox" name="solo_urgentes" value="1"
                    @checked(request('solo_urgentes'))
                    class="rounded border-teal-900/25 text-[#E8735C] focus:ring-[#E8735C]"
                >
                Solo derivación inmediata
            </label>

            <div class="flex w-full gap-3 sm:w-auto">
                <button type="submit" class="flex-1 rounded-lg bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900 sm:flex-none">
                    Filtrar
                </button>
                @if (request()->anyFilled(['q', 'urgency_level', 'specialty_id', 'solo_urgentes']))
                    <a href="{{ route('administrator.symptoms.index') }}" class="flex items-center text-sm text-teal-700/70 underline hover:text-teal-900">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabla: vista completa desde md hacia arriba --}}
        <div class="hidden overflow-x-auto rounded-xl border border-teal-900/10 bg-white shadow-sm md:block">
            <table class="min-w-full divide-y divide-teal-900/10 text-sm">
                <thead class="bg-teal-50/60">
                    <tr class="text-left text-xs uppercase tracking-wide text-teal-700/70">
                        <th class="px-4 py-3">Síntoma buscado</th>
                        <th class="px-4 py-3">Especialidad</th>
                        <th class="px-4 py-3">Urgencia</th>
                        <th class="px-4 py-3 text-right">Búsquedas</th>
                        <th class="px-4 py-3">Recomendación IA</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-teal-900/5">
                    @forelse ($symptoms as $symptom)
                        <tr class="{{ $symptom->esUrgente() ? 'bg-[#FBEAE6]/40' : '' }} hover:bg-teal-50/40">
                            <td class="px-4 py-3">
                                <p class="font-medium text-teal-900">{{ $symptom->search_query }}</p>
                                <a
                                    href="{{ url('/sintomas/' . $symptom->slug) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-xs text-teal-700/50 underline decoration-dotted hover:text-teal-900"
                                >
                                    {{ $symptom->slug }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-teal-800">
                                {{ $symptom->specialty->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeColors = [
                                        'Baja' => 'bg-teal-100 text-teal-800',
                                        'Media' => 'bg-amber-100 text-amber-800',
                                        'Alta' => 'bg-[#E8735C] text-white',
                                    ];
                                    $badgeClass = $badgeColors[$symptom->urgency_level] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">
                                    {{ $symptom->urgency_level ?? 'Sin definir' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right" style="font-family: 'IBM Plex Mono', monospace;">
                                {{ $symptom->search_count }}
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <p class="line-clamp-2 text-xs text-teal-800/80">
                                    {{ $symptom->ai_advice ?? '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button
                                    @click='openEdit(@json($symptom))'
                                    title="Editar"
                                    class="mr-2 inline-flex rounded-lg p-1.5 text-teal-700 hover:bg-teal-100 hover:text-teal-900"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.25 2.25 0 1 1 3.182 3.182L7.5 20.213l-4 1 1-4L16.862 4.487Z"/>
                                    </svg>
                                    <span class="sr-only">Editar</span>
                                </button>
                                <form action="{{ route('administrator.symptoms.destroy', $symptom) }}" method="POST" class="inline"
                                    onsubmit="return confirm('¿Eliminar este síntoma indexado?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Eliminar"
                                            class="inline-flex rounded-lg p-1.5 text-[#b3452f] hover:bg-[#FBEAE6] hover:text-[#8f3623]">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                        <span class="sr-only">Eliminar</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-teal-700/60">
                                No hay síntomas que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tarjetas: vista mobile, debajo de md --}}
        <div class="space-y-3 md:hidden">
            @forelse ($symptoms as $symptom)
                @php
                    $badgeColors = [
                        'Baja' => 'bg-teal-100 text-teal-800',
                        'Media' => 'bg-amber-100 text-amber-800',
                        'Alta' => 'bg-[#E8735C] text-white',
                    ];
                    $badgeClass = $badgeColors[$symptom->urgency_level] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <div class="rounded-xl border border-teal-900/10 {{ $symptom->esUrgente() ? 'bg-[#FBEAE6]/40' : 'bg-white' }} p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-teal-900">{{ $symptom->search_query }}</p>
                            <a
                                href="{{ url('/sintomas/' . $symptom->slug) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block truncate text-xs text-teal-700/50 underline decoration-dotted hover:text-teal-900"
                            >
                                {{ $symptom->slug }}
                            </a>
                        </div>
                        <span class="shrink-0 inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">
                            {{ $symptom->urgency_level ?? 'Sin definir' }}
                        </span>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-y-1 text-xs">
                        <dt class="text-teal-700/60">Especialidad</dt>
                        <dd class="text-right text-teal-800">{{ $symptom->specialty->name ?? '—' }}</dd>
                        <dt class="text-teal-700/60">Búsquedas</dt>
                        <dd class="text-right text-teal-800" style="font-family: 'IBM Plex Mono', monospace;">
                            {{ $symptom->search_count }}
                        </dd>
                    </dl>

                    @if ($symptom->ai_advice)
                        <p class="mt-3 line-clamp-3 text-xs text-teal-800/80">{{ $symptom->ai_advice }}</p>
                    @endif

                    <div class="mt-4 flex items-center justify-end gap-2 border-t border-teal-900/5 pt-3">
                        <button
                            @click='openEdit(@json($symptom))'
                            title="Editar"
                            class="inline-flex rounded-lg p-2 text-teal-700 hover:bg-teal-100 hover:text-teal-900"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.25 2.25 0 1 1 3.182 3.182L7.5 20.213l-4 1 1-4L16.862 4.487Z"/>
                            </svg>
                            <span class="sr-only">Editar</span>
                        </button>
                        <form action="{{ route('administrator.symptoms.destroy', $symptom) }}" method="POST"
                            onsubmit="return confirm('¿Eliminar este síntoma indexado?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Eliminar"
                                    class="inline-flex rounded-lg p-2 text-[#b3452f] hover:bg-[#FBEAE6] hover:text-[#8f3623]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                                <span class="sr-only">Eliminar</span>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-teal-900/10 bg-white p-8 text-center text-sm text-teal-700/60">
                    No hay síntomas que coincidan con los filtros.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $symptoms->links() }}
        </div>

        {{-- Modal crear/editar (Alpine) --}}
        <div
            x-show="modalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-teal-950/40 p-4"
            style="display: none;"
        >
            <div
                @click.outside="closeModal()"
                x-show="modalOpen"
                x-transition
                class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-[#F4F1EA] p-5 shadow-xl sm:p-6"
            >
                <h2 class="mb-4 text-xl font-semibold text-teal-900" style="font-family: 'Fraunces', serif;"
                    x-text="isEditing ? 'Editar síntoma' : 'Nuevo síntoma'"></h2>

                <form :action="formAction" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="isEditing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-teal-700/70">Búsqueda del paciente</label>
                        <input
                            type="text" name="search_query" x-model="form.search_query" required
                            class="w-full rounded-lg border-teal-900/15 text-sm focus:border-[#E8735C] focus:ring-[#E8735C]"
                        >
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-teal-700/70">Especialidad</label>
                            <select name="specialty_id" x-model="form.specialty_id"
                                    class="w-full rounded-lg border-teal-900/15 text-sm focus:border-[#E8735C] focus:ring-[#E8735C]">
                                <option value="">Sin asignar</option>
                                @foreach ($specialties as $specialty)
                                    <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-teal-700/70">Urgencia</label>
                            <select name="urgency_level" x-model="form.urgency_level"
                                    class="w-full rounded-lg border-teal-900/15 text-sm focus:border-[#E8735C] focus:ring-[#E8735C]">
                                @foreach (\App\Models\IndexedSymptom::NIVELES_URGENCIA as $nivel)
                                    <option value="{{ $nivel }}">{{ $nivel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-teal-700/70">Título SEO</label>
                        <input
                            type="text" name="seo_title" x-model="form.seo_title"
                            class="w-full rounded-lg border-teal-900/15 text-sm focus:border-[#E8735C] focus:ring-[#E8735C]"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-teal-700/70">Descripción SEO</label>
                        <textarea
                            name="seo_description" x-model="form.seo_description" rows="2"
                            class="w-full rounded-lg border-teal-900/15 text-sm focus:border-[#E8735C] focus:ring-[#E8735C]"
                        ></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-teal-700/70">Recomendación / consejo IA</label>
                        <textarea
                            name="ai_advice" x-model="form.ai_advice" rows="3"
                            class="w-full rounded-lg border-teal-900/15 text-sm focus:border-[#E8735C] focus:ring-[#E8735C]"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeModal()"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-100">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-[#E8735C] px-4 py-2 text-sm font-medium text-white hover:bg-[#d9614a]">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function symptomsAdmin() {
        return {
            modalOpen: false,
            isEditing: false,
            formAction: '{{ route('administrator.symptoms.store') }}',
            form: {
                search_query: '',
                specialty_id: '',
                urgency_level: 'Media',
                seo_title: '',
                seo_description: '',
                ai_advice: '',
            },
            openCreate() {
                this.isEditing = false;
                this.formAction = '{{ route('administrator.symptoms.store') }}';
                this.form = {
                    search_query: '',
                    specialty_id: '',
                    urgency_level: 'Media',
                    seo_title: '',
                    seo_description: '',
                    ai_advice: '',
                };
                this.modalOpen = true;
            },
            openEdit(symptom) {
                this.isEditing = true;
                this.formAction = `/administrator/seo-sintomas/${symptom.id}`;
                this.form = {
                    search_query: symptom.search_query ?? '',
                    specialty_id: symptom.specialty_id ?? '',
                    urgency_level: symptom.urgency_level ?? 'Media',
                    seo_title: symptom.seo_title ?? '',
                    seo_description: symptom.seo_description ?? '',
                    ai_advice: symptom.ai_advice ?? '',
                };
                this.modalOpen = true;
            },
            closeModal() {
                this.modalOpen = false;
            },
        }
    }
    </script>
</x-admin-layout>
