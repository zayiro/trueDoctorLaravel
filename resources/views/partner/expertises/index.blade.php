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
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Columna Izquierda: Formulario de Registro -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-blue-600 px-6 py-4">
                        <h5 class="text-lg font-semibold text-white">Agregar Enfermedad o Dolencia</h5>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('partner.expertises.store') }}" method="POST" id="expertise-form">
                            @csrf
                            
                            <div class="mb-4">
                                <label for="disease_name" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Enfermedad</label>
                                <input type="text" name="disease_name" id="disease_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ej: Migraña, Gastritis, Escoliosis" required>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Palabras clave o Síntomas cotidianos</label>
                                
                                <!-- Input visual de escritura -->
                                <input type="text" id="tag-input" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-1" placeholder="Escribe un síntoma y presiona Enter o Coma">
                                <p class="text-xs text-gray-500 mb-3">Piensa en cómo lo buscaría el paciente. Ej: "dolor de cabeza".</p>
                                
                                <!-- Contenedor dinámico de tags visuales -->
                                <div id="tags-container" class="flex flex-wrap gap-1.5 p-2 bg-gray-50 rounded-md border border-gray-200 min-h-[60px]"></div>
                                
                                <!-- Campo oculto para Laravel -->
                                <input type="hidden" name="symptoms_keywords" id="hidden-symptoms-input">
                            </div>

                            <!-- 💡 NUEVO: Bloque de Texto de Ayuda / Buenas Prácticas para el Médico -->
                            <div class="mb-5 bg-blue-50 border border-blue-100 rounded-md p-4">
                                <div class="flex items-start gap-2">
                                    <span class="text-blue-500 text-base mt-0.5">💡</span>
                                    <div>
                                        <h6 class="text-xs font-bold text-blue-900 uppercase tracking-wider mb-2">¿Cómo escribir palabras clave efectivas?</h6>
                                        <ul class="text-xs text-blue-800 space-y-1.5 list-disc pl-4">
                                            <li><strong>Evita tecnicismos:</strong> El paciente no buscará <em>"Cefalea"</em>, buscará <em>"dolor de cabeza"</em>. El sistema los conectará por ti.</li>
                                            <li><strong>Usa lenguaje común:</strong> Registra términos populares como <em>"agrieras"</em>, <em>"reflujo"</em>, <em>"ardor en el pecho"</em> o <em>"vómito"</em>.</li>
                                            <li><strong>Describe dolores físicos:</strong> Piensa en cómo lo explicarían en consulta: <em>"hinchazón de pies"</em>, <em>"falta de aire"</em> o <em>"mareos"</em>.</li>
                                            <li><strong>Evita tildes si es posible:</strong> Aunque el buscador es inteligente, escribir <em>"presion alta"</em> o <em>"alergia"</em> simplifica el proceso de coincidencia.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-md transition duration-150 ease-in-out">
                                Guardar Conocimiento
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Tabla de Registros Actuales -->
            <div class="md:col-span-2">
                @if(session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 p-4 rounded-md flex justify-between items-center" role="alert">
                        <span>{{ session('success') }}</span>
                        <button type="button" class="text-green-600 hover:text-green-800 font-bold" onclick="this.parentElement.remove()">✕</button>
                    </div>
                @endif

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-white border-b border-gray-200 px-6 py-4">
                        <h5 class="text-lg font-semibold text-gray-900">Mis Enfermedades y Síntomas Registrados</h5>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold border-b border-gray-200">
                                    <th class="px-6 py-3">Enfermedad</th>
                                    <th class="px-6 py-3">Palabras Clave / Síntomas asociados</th>
                                    <th class="px-6 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                                @forelse($expertises as $expertise)
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 font-bold text-gray-900">{{ $expertise->disease_name }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(explode(',', $expertise->symptoms_keywords) as $keyword)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                        {{ trim($keyword) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-3">
                                                <a href="{{ route('partner.expertises.edit', $expertise->id) }}" class="text-gray-600 hover:text-gray-900 font-medium">Editar</a>
                                                
                                                <form action="{{ route('partner.expertises.destroy', $expertise->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este registro?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 font-medium">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-12 text-gray-500">
                                            Aún no has registrado enfermedades. Usa el formulario para comenzar.
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

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tagInput = document.getElementById("tag-input");
            const tagsContainer = document.getElementById("tags-container");
            const hiddenInput = document.getElementById("hidden-symptoms-input");
            const form = document.getElementById("expertise-form");

            // Arreglo global para almacenar los strings de los tags
            let tagsArray = [];

            // Si el input oculto ya contiene datos (caso de la vista Editar), los cargamos al iniciar
            if (hiddenInput.value.trim() !== "") {
                tagsArray = hiddenInput.value.split(",").map(tag => tag.trim()).filter(tag => tag !== "");
                renderTags();
            }

            // Escuchar cuando el usuario escribe en el input de texto
            tagInput.addEventListener("keydown", function (e) {
                // Captura las teclas 'Enter' (13) o 'Coma' (188 o e.key === ',')
                if (e.key === "Enter" || e.key === ",") {
                    e.preventDefault(); // Evitamos que el formulario se envíe al presionar Enter
                    
                    let value = tagInput.value.trim().replace(/,/g, ""); // Limpiamos espacios y comas sobrantes
                    
                    // Validamos que no esté vacío y que no esté repetido el síntoma
                    if (value !== "" && !tagsArray.includes(value)) {
                        tagsArray.push(value);
                        renderTags();
                        tagInput.value = ""; // Limpiamos el input de escritura
                    }
                }
            });

            // Función para dibujar los tags en la pantalla y actualizar el input oculto
            function renderTags() {
                tagsContainer.innerHTML = ""; // Limpiamos el contenedor
                
                tagsArray.forEach((tag, index) => {
                    // Creamos el diseño visual del tag estilo Bootstrap 5
                    const tagElement = document.createElement("span");
                    tagElement.className = "inline-flex items-center gap-1.5 bg-blue-600 text-white text-sm py-1 px-3 rounded-full shadow-sm";
                    tagElement.innerHTML = `
                        ${tag}
                        <button type="button" class="text-blue-200 hover:text-white font-bold ml-1 focus:outline-none" style="line-height: 1;" data-index="${index}">✕</button>
                    `;

                    tagsContainer.appendChild(tagElement);
                });

                // Sincronizamos el array con el input oculto separados por comas para Laravel
                hiddenInput.value = tagsArray.join(", ");
            }

            // Escuchar los clics en los botones de cerrar (X) para eliminar etiquetas
            tagsContainer.addEventListener("click", function (e) {
                if (e.target.classList.contains("btn-close")) {
                    const indexToRemove = e.target.getAttribute("data-index");
                    tagsArray.splice(indexToRemove, 1); // Lo removemos del arreglo interno
                    renderTags(); // Redibujamos la lista actualizada
                }
            });

            // Validación final de seguridad antes de enviar el formulario a la base de datos
            form.addEventListener("submit", function (e) {
                if (tagsArray.length === 0) {
                    e.preventDefault();
                    alert("Por favor, introduce al menos un síntoma o palabra clave presionando Enter.");
                    tagInput.focus();
                }
            });
        });
    </script>
</x-admin-layout>    
