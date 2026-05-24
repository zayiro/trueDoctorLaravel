<x-guest-layout>
    <div class="max-w-5xl mx-auto py-12 px-4 mt-6">        
        <div class="w-full bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xl shadow-slate-100/70">
            <!-- Encabezado -->
            <div class="mb-6 space-y-1">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Analizar nuevo examen</h1>
                <p class="text-sm text-slate-500">Sube tus reportes y obtén una interpretación médica guiada por Inteligencia Artificial.</p>
            </div>

            <!-- Alertas de Errores Dinámicas de JavaScript y Laravel -->
            <div id="js-error-container" class="hidden mb-5 bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-xs font-semibold"></div>

            @if ($errors->any())
                <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-xs font-semibold space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('exams.store') }}" method="POST" enctype="multipart/form-data" id="exam-form" class="space-y-5">
                @csrf

                <!-- Zona de Carga de Archivo -->
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-indigo-500 transition-colors group relative cursor-pointer bg-slate-50/50">
                    <input type="file" name="exam_file" id="exam_file" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="space-y-2 pointer-events-none">
                        <svg class="w-8 h-8 mx-auto text-slate-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/>
                        </svg>
                        <!-- Texto dinámico que cambiará al seleccionar un archivo -->
                        <p id="file-text-label" class="text-sm font-bold text-slate-700">Selecciona o arrastra tu archivo (PDF, JPG, PNG)</p>
                        <p class="text-xs text-slate-400">Tamaño máximo de archivo: 10MB</p>
                    </div>
                </div>

                <!-- Campo de Correo Electrónico -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">¿A qué correo electrónico enviamos tu resultado?</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25 2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615m19.5 0A2.25 2.25 0 0 0 19.5 4.5"></path>
                            </svg>
                        </span>
                        <input type="email" name="customer_email" required 
                            value="{{ auth()->check() ? auth()->user()->email : old('customer_email') }}" 
                            placeholder="ejemplo@correo.com" 
                            class="w-full pl-10 p-3 bg-white border border-slate-200 rounded-xl font-medium text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 placeholder:text-slate-400/80 shadow-xs">
                    </div>
                    <p class="text-[10px] text-slate-400 font-medium">Te enviaremos una copia digital y tu comprobante de pago a esta dirección.</p>
                </div>

                <!-- Campo del Motivo -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">¿Cuál es el motivo de estos exámenes?</label>
                    <select name="reason_type" required class="w-full p-3 bg-white border border-slate-200 rounded-xl font-medium text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 shadow-xs">
                        <option value="rutina">Control de rutina anual</option>
                        <option value="control">Seguimiento de una enfermedad existente</option>
                        <option value="sintomas">Por síntomas recientes que me preocupan</option>
                        <option value="otros">Otro motivo</option>
                    </select>
                </div>

                <!-- Detalles Adicionales -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Detalles o síntomas adicionales (Opcional)</label>
                    <textarea name="reason_custom" rows="3" placeholder="Ej: Sufro de colesterol alto y mi médico me ordenó este control trimestral..." class="w-full p-3 border border-slate-200 rounded-xl font-medium text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 shadow-xs placeholder:text-slate-400/80"></textarea>
                </div>

                <!-- Botón de Envío con Estado de Carga -->
                <div class="pt-2">
                    <button type="submit" id="submit-btn" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-100 hover:shadow-indigo-200/80 active:scale-[0.99] transition-all duration-200 flex items-center justify-center gap-2 shadow-lg">
                        <span id="btn-text">Proceder al pago seguro</span>
                        <svg id="btn-icon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- SCRIPT DE VALIDACIÓN Y ANIMACIÓN -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const fileInput = document.getElementById('exam_file');
                const fileLabel = document.getElementById('file-text-label');
                const errorContainer = document.getElementById('js-error-container');
                const examForm = document.getElementById('exam-form');
                const submitBtn = document.getElementById('submit-btn');
                const btnText = document.getElementById('btn-text');
                const btnIcon = document.getElementById('btn-icon');

                // 1. Escuchar cuando el usuario selecciona un archivo
                fileInput.addEventListener('change', function () {
                    errorContainer.classList.add('hidden');
                    errorContainer.innerText = '';

                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const maxSize = 10 * 1024 * 1024; // 10 Megabytes en bytes

                        // Mejora 1: Validar peso antes de subir al servidor
                        if (file.size > maxSize) {
                            errorContainer.innerText = '• El archivo seleccionado excede el límite permitido de 10MB. Por favor optimiza tu documento o sube uno más liviano.';
                            errorContainer.classList.remove('hidden');
                            this.value = ''; // Resetea el input para bloquear el archivo pesado
                            fileLabel.innerText = 'Selecciona o arrastra tu archivo (PDF, JPG, PNG)';
                            fileLabel.classList.remove('text-indigo-600');
                            return;
                        }

                        // Feedback visual: Muestra el nombre del archivo seleccionado de forma elegante
                        fileLabel.innerText = `📄 Archivo listo: ${file.name}`;
                        fileLabel.classList.add('text-indigo-600');
                    }
                });

                // 2. Controlar la animación del Spinner al enviar el formulario
                examForm.addEventListener('submit', function (e) {
                    // Verificar que el formulario sea realmente válido antes de disparar la carga animada
                    if (!examForm.checkValidity()) {
                        return;
                    }

                    // Mejora 2: Activar Spinner y bloquear re-envíos accidentales
                    submitBtn.disabled = true;
                    submitBtn.classList.add('bg-indigo-500', 'cursor-not-allowed');
                    submitBtn.classList.remove('hover:bg-indigo-700', 'active:scale-[0.99]');
                    btnText.innerText = 'Subiendo examen...';

                    // Inyectar el círculo animado SVG dinámicamente
                    btnIcon.outerHTML = `
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    `;
                });
            });
        </script>
    </div>
</x-guest-layout>