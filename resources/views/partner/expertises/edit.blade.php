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
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-7xl mx-auto">
            
            <!-- Tarjeta Principal de Edición -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                
                <!-- Encabezado -->
                <div class="bg-white border-b border-gray-200 px-6 py-4">
                    <h5 class="text-lg font-semibold text-gray-900">Editar Enfermedad Tratada</h5>
                    <p class="text-xs text-gray-500 mt-0.5">Modifica los términos o añade nuevos síntomas para optimizar las búsquedas de tus pacientes.</p>
                </div>
                
                <!-- Cuerpo del Formulario -->
                <div class="p-6">
                    <form action="{{ route('partner.expertises.update', $expertise->id) }}" method="POST" id="expertise-form">
                        @csrf
                        @method('PUT')

                        <!-- Nombre de la Enfermedad -->
                        <div class="mb-5">
                            <label for="disease_name" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Enfermedad</label>
                            <input 
                                type="text" 
                                name="disease_name" 
                                id="disease_name" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" 
                                value="{{ $expertise->disease_name }}" 
                                required
                            >
                        </div>

                        <!-- Tags / Síntomas Dinámicos -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Palabras clave o Síntomas cotidianos</label>
                            
                            <!-- Input visual de escritura -->
                            <input 
                                type="text" 
                                id="tag-input" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm mb-1" 
                                placeholder="Escribe un síntoma y presiona Enter o Coma"
                            >
                            <p class="text-xs text-gray-500 mb-3">Añade o elimina términos de la lista. Presiona Enter para confirmar cada uno.</p>
                            
                            <!-- Contenedor dinámico de tags visuales -->
                            <div id="tags-container" class="flex flex-wrap gap-1.5 p-2.5 bg-gray-50 rounded-md border border-gray-200 min-h-[60px]"></div>
                            
                            <!-- Inicializamos con el valor guardado en BD -->
                            <input type="hidden" name="symptoms_keywords" id="hidden-symptoms-input" value="{{ $expertise->symptoms_keywords }}">
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <a 
                                href="{{ route('partner.expertises.index') }}" 
                                class="bg-white hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm transition duration-150 ease-in-out"
                            >
                                Cancelar
                            </a>
                            
                            <button 
                                type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-5 rounded-md shadow-sm text-sm transition duration-150 ease-in-out"
                            >
                                Actualizar Cambios
                            </button>
                        </div>
                    </form>
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

            // Cargar datos existentes desde la base de datos
            if (hiddenInput.value.trim() !== "") {
                tagsArray = hiddenInput.value.split(",").map(tag => tag.trim()).filter(tag => tag !== "");
                renderTags();
            }

            // Escuchar cuando el usuario escribe en el input
            tagInput.addEventListener("keydown", function (e) {
                if (e.key === "Enter" || e.key === ",") {
                    e.preventDefault(); 
                    
                    let value = tagInput.value.trim().replace(/,/g, ""); 
                    
                    if (value !== "" && !tagsArray.includes(value)) {
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
                    // Estilos Tailwind unificados para el contenedor de la etiqueta
                    tagElement.className = "inline-flex items-center gap-1.5 bg-blue-600 text-white text-sm py-1 px-3 rounded-full shadow-sm";
                    
                    // Estructura interna del tag con clases específicas para el botón de cerrar
                    tagElement.innerHTML = `
                        <span>${tag}</span>
                        <button type="button" class="remove-tag-btn text-blue-200 hover:text-white font-bold ml-1 focus:outline-none pointer-events-auto cursor-pointer" style="line-height: 1;" data-index="${index}">✕</button>
                    `;
                    tagsContainer.appendChild(tagElement);
                });

                // Sincronizamos el array con el input oculto para Laravel
                hiddenInput.value = tagsArray.join(", ");
            }

            // SOLUCIÓN: Capturar el evento de eliminación de forma precisa
            tagsContainer.addEventListener("click", function (e) {
                // Buscamos si el clic ocurrió en el botón de cerrar (o dentro de él)
                const button = e.target.closest(".remove-tag-btn");
                
                if (button) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Obtenemos el índice del atributo personalizado
                    const indexToRemove = parseInt(button.getAttribute("data-index"), 10);
                    
                    // Eliminamos la posición del arreglo, actualizamos la interfaz y el campo oculto
                    tagsArray.splice(indexToRemove, 1); 
                    renderTags(); 
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
