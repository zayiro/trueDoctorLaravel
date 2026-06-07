@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Mis Conocimientos Médicos',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-10 max-w-7xl">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- Columna Izquierda: Formulario de Registro Premium -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-100/40 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <!-- Cabecera de Tarjeta -->
                    <div class="bg-blue-600 px-6 py-5">
                        <h5 class="text-lg font-black text-white tracking-tight">Agregar Enfermedad o Dolencia</h5>
                        <p class="text-xs text-blue-100 mt-1">Alimenta tu motor de búsqueda FullText.</p>
                    </div>
                    
                    <div class="p-6 md:p-8">
                        <form action="{{ route('partner.expertises.store') }}" method="POST" id="expertise-form">
                            @csrf
                            
                            <!-- Campo: Nombre de la Enfermedad -->
                            <div class="mb-5">
                                <label for="disease_name" class="block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Nombre de la Enfermedad</label>
                                <input type="text" name="disease_name" id="disease_name" 
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition duration-150 text-sm py-3 px-4 shadow-sm" 
                                    placeholder="Ej: Migraña, Gastritis, Escoliosis" required>
                            </div>

                            <!-- Campo: Síntomas y Tags Dinámicos -->
                            <div class="mb-6">
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Palabras clave o Síntomas cotidianos</label>
                                
                                <input type="text" id="tag-input" 
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition duration-150 text-sm py-3 px-4 shadow-sm mb-2" 
                                    placeholder="Escribe un síntoma y presiona Enter o Coma">
                                
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-1">
                                    <span>💡</span> Piensa en cómo lo buscaría el paciente. Ej: "dolor de cabeza".
                                </p>
                                
                                <!-- Contenedor Dinámico Flotante de Tags -->
                                <div id="tags-container" class="flex flex-wrap gap-2 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700 min-h-[70px] content-start transition duration-150"></div>
                                
                                <!-- Campo Oculto para Laravel -->
                                <input type="hidden" name="symptoms_keywords" id="hidden-symptoms-input">
                            </div>

                            <!-- Sugerencias de Buenas Prácticas UX Premium -->
                            <div class="mb-6 bg-gradient-to-br from-blue-50 to-indigo-50/50 dark:from-blue-950/20 dark:to-transparent border border-blue-100/70 dark:border-blue-900/30 rounded-2xl p-4 md:p-5">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center font-bold text-sm">
                                        💡
                                    </div>
                                    <div>
                                        <h6 class="text-xs font-black text-blue-900 dark:text-blue-400 uppercase tracking-wider mb-2">¿Cómo escribir palabras clave efectivas?</h6>
                                        <ul class="text-xs text-blue-800/90 dark:text-gray-400 space-y-2 list-none">
                                            <li class="flex items-start gap-1.5"><span class="text-blue-500 font-bold">•</span><span><strong>Evita tecnicismos:</strong> El paciente buscará <em>"dolor de cabeza"</em>, no <em>"Cefalea"</em>.</span></li>
                                            <li class="flex items-start gap-1.5"><span class="text-blue-500 font-bold">•</span><span><strong>Usa lenguaje común:</strong> Registra términos populares como <em>"agrieras"</em> o <em>"reflujo"</em>.</span></li>
                                            <li class="flex items-start gap-1.5"><span class="text-blue-500 font-bold">•</span><span><strong>Describe dolores físicos:</strong> Piensa en frases de consulta: <em>"falta de aire"</em> o <em>"mareos"</em>.</span></li>
                                            <li class="flex items-start gap-1.5"><span class="text-blue-500 font-bold">•</span><span><strong>Evita tildes si es posible:</strong> Escribir <em>"presion alta"</em> simplifica las coincidencias.</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón de Acción con Animación Táctil -->
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black uppercase text-[12px] tracking-wider py-3.5 px-4 rounded-xl transition duration-200 shadow-md shadow-blue-500/10 active:scale-[0.99] focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900/30">
                                Guardar Conocimiento
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Columna Derecha: Tabla de Registros Actuales -->
            <div class="lg:col-span-2 space-y-4">
                @if(session('success'))
                    <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 text-emerald-800 dark:text-emerald-400 p-4 rounded-2xl flex justify-between items-center text-sm font-semibold transition duration-150" role="alert">
                        <div class="flex items-center gap-2">
                            <span class="text-base">✓</span>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button type="button" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 font-bold transition-colors" onclick="this.parentElement.remove()">✕</button>
                    </div>
                @endif

                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-100/40 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 px-6 py-5">
                        <h5 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">Mis Enfermedades y Síntomas Registrados</h5>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-[11px] uppercase font-black tracking-wider border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-6 py-4">Enfermedad</th>
                                    <th class="px-6 py-4">Palabras Clave / Síntomas asociados</th>
                                    <th class="px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                                @forelse($expertises as $expertise)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition duration-150">
                                        <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ $expertise->disease_name }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach(explode(',', $expertise->symptoms_keywords) as $keyword)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-gray-50 text-gray-600 border border-gray-200/60 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 shadow-sm">
                                                        {{ trim($keyword) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end items-center gap-2">
                                                <!-- Botón Editar Premium -->
                                                <a href="{{ route('partner.expertises.edit', $expertise->id) }}" 
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 rounded-xl border border-gray-100 dark:border-gray-600 text-xs font-bold transition duration-150 shadow-sm">
                                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/>
                                                    </svg>
                                                    Editar
                                                </a>
                                                
                                                <!-- Formulario y Botón Eliminar Premium -->
                                                <form action="{{ route('partner.expertises.destroy', $expertise->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este conocimiento médico?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-950/30 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-xl border border-red-100 dark:border-red-900/30 text-xs font-bold transition duration-150 shadow-sm">
                                                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.34 9m-4.74 0L9.36 9m12 1.5c0 2.112-.754 4.135-2.115 5.593A5.436 5.436 0 0 1 15 17.5H9a5.436 5.436 0 0 1-4.245-1.407C3.394 14.635 2.64 12.612 2.64 10.5m18.72 0H2.64m18.72 0a2.25 2.25 0 0 0-2.25-2.25H4.89a2.25 2.25 0 0 0-2.25 2.25m18.72 0V5.75A2.25 2.25 0 0 0 16.5 3.5h-9A2.25 2.25 0 0 0 5.25 5.75V10.5"/>
                                                        </svg>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <!-- UX Ilustrada para Estado Vacío -->
                                    <tr>
                                        <td colspan="3" class="text-center py-16 px-4">
                                            <div class="mx-auto w-12 h-12 bg-gray-50 dark:bg-gray-900 text-gray-400 rounded-full flex items-center justify-center mb-3">
                                                🔍
                                            </div>
                                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">Aún no has registrado enfermedades</p>
                                            <p class="text-xs text-gray-400 mt-0.5 max-w-sm mx-auto">Usa el formulario lateral para alimentar tus palabras clave de síntomas cotidianos y optimizar tus búsquedas.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- Motor JavaScript Refactorizado y Blindado -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tagInput = document.getElementById("tag-input");
            const tagsContainer = document.getElementById("tags-container");
            const hiddenInput = document.getElementById("hidden-symptoms-input");
            const form = document.getElementById("expertise-form");

            let tagsArray = [];

            if (hiddenInput && hiddenInput.value.trim() !== "") {
                tagsArray = hiddenInput.value.split(",").map(tag => tag.trim()).filter(tag => tag !== "");
                renderTags();
            }

            tagInput.addEventListener("keydown", function (e) {
                if (e.key === "Enter" || e.key === ",") {
                    e.preventDefault(); 
                    
                    let value = tagInput.value.trim().replace(/,/g, ""); 
                    
                    if (value !== "" && !tagsArray.some(t => t.toLowerCase() === value.toLowerCase())) {
                        tagsArray.push(value);
                        renderTags();
                        tagInput.value = ""; 
                    }
                }
            });

            function renderTags() {
                tagsContainer.innerHTML = ""; 
                
                tagsArray.forEach((tag, index) => {
                    const tagElement = document.createElement("span");
                    tagElement.className = "inline-flex items-center gap-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-1.5 px-3 rounded-xl shadow-sm transition duration-150 select-none";
                    tagElement.innerHTML = `
                        <span>${tag}</span>
                        <button type="button" 
                                class="tag-close-btn text-blue-200 hover:text-white font-bold ml-1 transition-colors focus:outline-none" 
                                style="line-height: 1;" 
                                data-index="${index}">✕</button>
                    `;

                    tagsContainer.appendChild(tagElement);
                });

                hiddenInput.value = tagsArray.join(", ");
            }

            tagsContainer.addEventListener("click", function (e) {
                if (e.target.classList.contains("tag-close-btn")) {
                    const indexToRemove = parseInt(e.target.getAttribute("data-index"), 10);
                    tagsArray.splice(indexToRemove, 1); 
                    renderTags(); 
                    tagInput.focus();
                }
            });

            form.addEventListener("submit", function (e) {
                if (tagsArray.length === 0) {
                    e.preventDefault();
                    alert("Por favor, introduce al menos un síntoma o palabra clave presionando Enter para alimentar el motor FullText.");
                    tagInput.focus();
                }
            });
        });
    </script>
</x-admin-layout>
