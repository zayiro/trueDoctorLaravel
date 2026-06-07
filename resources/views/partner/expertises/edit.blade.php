@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Editar Conocimientos Médicos',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-10 max-w-4xl">
        <div class="max-w-7xl mx-auto">
            
            <!-- Tarjeta Principal de Edición Premium -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-100/40 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
                
                <!-- Encabezado con Contraste Inteligente -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 px-6 py-5">
                    <h5 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">Editar Enfermedad Tratada</h5>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Modifica los términos o añade nuevos síntomas para optimizar las búsquedas FullText de tus pacientes.</p>
                </div>
                
                <!-- Cuerpo del Formulario -->
                <div class="p-6 md:p-8">
                    <form action="{{ route('partner.expertises.update', $expertise->id) }}" method="POST" id="expertise-form">
                        @csrf
                        @method('PUT')

                        <!-- Campo: Nombre de la Enfermedad -->
                        <div class="mb-5">
                            <label for="disease_name" class="block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Nombre de la Enfermedad</label>
                            <input 
                                type="text" 
                                name="disease_name" 
                                id="disease_name" 
                                class="w-full rounded-xl border-gray-200 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition duration-150 text-sm py-3 px-4 shadow-sm" 
                                value="{{ $expertise->disease_name }}" 
                                required
                            >
                        </div>

                        <!-- Campo: Tags / Síntomas Dinámicos -->
                        <div class="mb-6">
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Palabras clave o Síntomas cotidianos</label>
                            
                            <!-- Input visual de escritura -->
                            <input 
                                type="text" 
                                id="tag-input" 
                                class="w-full rounded-xl border-gray-200 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition duration-150 text-sm py-3 px-4 shadow-sm mb-2" 
                                placeholder="Escribe un síntoma y presiona Enter o Coma"
                            >
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-1">
                                <span>💡</span> Añade o elimina términos de la lista. Presiona Enter para confirmar cada uno.
                            </p>
                            
                            <!-- Contenedor dinámico flotante de tags visuales -->
                            <div id="tags-container" class="flex flex-wrap gap-2 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700 min-h-[70px] content-start transition duration-150"></div>
                            
                            <!-- Inicializamos con el valor guardado en BD -->
                            <input type="hidden" name="symptoms_keywords" id="hidden-symptoms-input" value="{{ $expertise->symptoms_keywords }}">
                        </div>

                        <!-- Botones de Acción con Alineación y Estilo Premium -->
                        <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <a 
                                href="{{ route('partner.expertises.index') }}" 
                                class="w-full sm:w-auto text-center bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-bold uppercase text-[11px] tracking-wider py-3 px-5 border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm transition duration-150 active:scale-[0.99]"
                            >
                                Cancelar
                            </a>
                            
                            <button 
                                type="submit" 
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-black uppercase text-[11px] tracking-wider py-3.5 px-6 rounded-xl transition duration-200 shadow-md shadow-blue-500/10 active:scale-[0.99] focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900/30"
                            >
                                Actualizar Cambios
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <!-- Motor JavaScript Refactorizado con tu Solución de Captura Precisa -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tagInput = document.getElementById("tag-input");
            const tagsContainer = document.getElementById("tags-container");
            const hiddenInput = document.getElementById("hidden-symptoms-input");
            const form = document.getElementById("expertise-form");

            // Arreglo global para almacenar los strings de los tags
            let tagsArray = [];

            // Cargar datos existentes desde la base de datos
            if (hiddenInput && hiddenInput.value.trim() !== "") {
                tagsArray = hiddenInput.value.split(",").map(tag => tag.trim()).filter(tag => tag !== "");
                renderTags();
            }

            // Escuchar cuando el usuario escribe en el input
            tagInput.addEventListener("keydown", function (e) {
                if (e.key === "Enter" || e.key === ",") {
                    e.preventDefault(); 
                    
                    let value = tagInput.value.trim().replace(/,/g, ""); 
                    
                    // Validación de duplicados insensibles a mayúsculas
                    if (value !== "" && !tagsArray.some(t => t.toLowerCase() === value.toLowerCase())) {
                        tagsArray.push(value);
                        renderTags();
                        tagInput.value = ""; 
                    }
                }
            });

            // Función para dibujar los tags en la pantalla y actualizar el input oculto
            function renderTags() {
                tagsContainer.innerHTML = ""; 
                
                tagsArray.forEach((tag, index) => {
                    const tagElement = document.createElement("span");
                    // Estilos Tailwind unificados e interactivos estilo Premium para las píldoras
                    tagElement.className = "inline-flex items-center gap-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-1.5 px-3 rounded-xl shadow-sm transition duration-150 select-none";
                    
                    // Estructura interna del tag con tus clases específicas de eliminación precisa
                    tagElement.innerHTML = `
                        <span>${tag}</span>
                        <button type="button" class="remove-tag-btn text-blue-200 hover:text-white font-bold ml-1 transition-colors focus:outline-none pointer-events-auto cursor-pointer" style="line-height: 1;" data-index="${index}">✕</button>
                    `;
                    tagsContainer.appendChild(tagElement);
                });

                // Sincronizamos el array con el input oculto para Laravel
                hiddenInput.value = tagsArray.join(", ");
            }

            // Capturar el evento de eliminación de forma precisa (Tu Solución Maestra)
            tagsContainer.addEventListener("click", function (e) {
                const button = e.target.closest(".remove-tag-btn");
                
                if (button) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const indexToRemove = parseInt(button.getAttribute("data-index"), 10);
                    
                    tagsArray.splice(indexToRemove, 1); 
                    renderTags(); 
                    tagInput.focus();
                }
            });

            // Validación final antes de enviar
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
