<x-guest-layout>
    <!-- Contenedor Principal del Formulario -->
    <div class="max-w-6xl w-full mx-auto px-6 py-12 flex-grow mt-6">
        <div class="bg-slate-950 rounded-2xl border border-white/10 p-8 shadow-2xl space-y-6">
            
            <div class="space-y-2">
                <h1 class="text-2xl font-black text-white">Centro de Análisis Clínico Avanzado</h1>
                <p class="text-sm text-slate-400">Arrastra o selecciona uno o múltiples informes médicos en formato PDF y obtén una interpretación médica guiada por Inteligencia Artificial.</p>
            </div>

            <!-- Zona Dropzone Interactiva -->
            <form id="uploadForm" enctype="multipart/form-data" class="space-y-6">
                <div id="dropzone" class="border-2 border-dashed border-white/20 hover:border-blue-500/50 bg-slate-900/50 rounded-xl p-8 text-center cursor-pointer transition-all relative">
                    <input type="file" name="pdf_files[]" id="pdf_files" class="hidden" multiple accept=".pdf">
                    
                    <div class="space-y-3 pointer-events-none" id="dropzonePrompt">
                        <div class="w-12 h-12 rounded-full bg-slate-600 text-blue-400 flex items-center justify-center mx-auto">
                            <!-- Heroicons: Cloud-Arrow-Up (SVG) -->
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-200">Haz clic para buscar o suelta tus archivos aquí</p>
                        <p class="text-xs text-slate-500">Solo archivos PDF (Máximo 5 informes, hasta 10MB por archivo)</p>
                    </div>

                    <div id="fileList" class="hidden text-left bg-slate-950/80 border border-white/5 p-4 rounded-xl space-y-2 text-xs"></div>
                </div>

                <!-- Nuevos Campos de Información del Paciente -->
                <div class="grid md:grid-cols-2 gap-6 text-left">
                    <!-- Campo de Correo Electrónico -->
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-bold tracking-wider text-white">¿A qué correo electrónico enviamos tu resultado?</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                                <!-- Heroicons: Envelope (SVG) -->
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615m19.5 0A2.25 2.25 0 0 0 19.5 4.5" />
                                </svg>
                            </span>
                            <input type="email" name="customer_email" required 
                                value="{{ auth()->check() ? auth()->user()->email : old('customer_email') }}" 
                                placeholder="ejemplo@correo.com" 
                                class="w-full pl-10 p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-slate-500 shadow-sm">
                        </div>
                        <p class="text-xs text-slate-400 font-medium">Te enviaremos el resultado digital a esta dirección</p>
                    </div>

                    <!-- Campo del Motivo -->
                    <div class="space-y-1.5">
                        <label class="text-sm font-bold tracking-wider text-white">¿Cuál es el motivo de estos exámenes?</label>
                        <select name="reason_type" required class="w-full p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                            <option value="rutina" class="bg-slate-950">Control de rutina anual</option>
                            <option value="control" class="bg-slate-950">Seguimiento de una enfermedad existente</option>
                            <option value="sintomas" class="bg-slate-950">Por síntomas recientes que me preocupan</option>
                            <option value="otros" class="bg-slate-950">Otro motivo</option>
                        </select>
                    </div>

                    <!-- Detalles Adicionales -->
                    <div class="space-y-1.5">
                        <label class="text-sm font-bold tracking-wider text-white">Detalles o síntomas adicionales (Opcional)</label>
                        <textarea name="reason_custom" rows="1" placeholder="Ej: Sufro de colesterol alto..." class="w-full p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm placeholder:text-slate-500 resize-none"></textarea>
                    </div>
                </div>

                <!-- Botón de Envío -->
                <button type="submit" id="submitBtn" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3.5 px-6 rounded-xl transition-all shadow-lg shadow-blue-500/10 flex items-center justify-center gap-3">
                    <span id="btnText">Iniciar Procesamiento con IA</span>
                    <!-- Spinner animado con clases nativas de Tailwind -->
                    <svg id="btnSpinner" class="animate-spin h-5 w-5 text-white hidden" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>

            </form>

            <!-- Estados del Análisis (Ocultos al inicio) -->
            <div id="loadingStatus" class="hidden bg-slate-900 border border-blue-500/20 rounded-xl p-6 space-y-4">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-blue-400 font-semibold" id="statusMessage"><i class="fa-solid fa-shield-halved mr-1"></i> Anonimizando documentos...</span>
                    <span class="text-slate-400 font-bold" id="progressPercentage">0%</span>
                </div>
                <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-white/5">
                    <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-emerald-500 text-white h-full w-0 transition-all duration-300"></div>
                </div>
            </div>          
        </div>
    </div>

    <span class="border-t border-white/5 py-4 text-center text-[10px] text-slate-600">
        <p>openDoctor AI. Procesamiento asíncrono seguro y cifrado de punta a punta.</p>
    </span>

    <script>
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('pdf_files');
    const fileList = document.getElementById('fileList');
    const dropzonePrompt = document.getElementById('dropzonePrompt');
    const uploadForm = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    const loadingStatus = document.getElementById('loadingStatus');
    const progressBar = document.getElementById('progressBar');
    const progressPercentage = document.getElementById('progressPercentage');
    const statusMessage = document.getElementById('statusMessage');
    const resultContainer = document.getElementById('resultContainer');
    const analysisOutput = document.getElementById('analysisOutput');

    // 1. Eventos del Input File y Arrastre de Archivos (Drag & Drop)
    dropzone.addEventListener('click', () => fileInput.click());

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-blue-500', 'bg-blue-500/5');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-blue-500', 'bg-blue-500/5');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-blue-500', 'bg-blue-500/5');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            updateFileList();
        }
    });

    fileInput.addEventListener('change', updateFileList);

    function updateFileList() {
        const files = fileInput.files;
        
        if (files.length > 5) {
            alert('Por seguridad y rendimiento, solo puedes analizar un máximo de 5 archivos PDF simultáneamente.');
            fileInput.value = "";
            fileList.classList.add('hidden');
            dropzonePrompt.classList.remove('hidden');
            return;
        }

        if (files.length === 0) return;

        fileList.innerHTML = '<p class="font-semibold text-slate-300 mb-1">Archivos listos para procesar:</p>';
        Array.from(files).forEach((file, index) => {
            // Renderizado usando un icono Document-Text de Heroicons en formato SVG inline
            fileList.innerHTML += `
                <div class="flex items-center text-slate-400 gap-2">
                    <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <span class="truncate">${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                </div>`;
        });
        
        fileList.classList.remove('hidden');
        dropzonePrompt.classList.add('hidden');
    }

    // 2. Envío Asíncrono mediante AJAX (Fetch)
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!fileInput.files.length) {
            alert('Por favor, selecciona al menos un archivo PDF.');
            return;
        }

        // Bloquear UI y activar loaders
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');

        btnSpinner.classList.remove('hidden');
        btnText.innerText = 'Procesando Documentos...';
        loadingStatus.classList.remove('hidden');
        
        // Simular progreso visual de carga inicial
        updateProgress(35, '<i class="fa-solid fa-user-shield mr-1 text-white"></i> Anonimizando de forma segura...');

        const formData = new FormData(uploadForm);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            setTimeout(() => {
                updateProgress(65, '<i class="fa-solid fa-brain mr-1 text-white"></i> Conectando con el Motor de Análisis Clínico...');
            }, 1000);

            const response = await fetch("{{ route('medical-analysis.process-documents') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken
                },
                body: formData
            });

            if (!response.ok) throw new Error('Error en el servidor médico.');

            const data = await response.json();

            if (data.status === 'success') {
                updateProgress(100, '<i class="fa-solid fa-check text-white mr-1"></i> Análisis Completado.');

                // 2. Redirigimos automáticamente al paciente a su vista de resultados única tras 1.5 segundos
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 1500);                            
            } else {
                alert('No se pudo completar el análisis del reporte.');
            }

        } catch (error) {            
            alert('Ocurrió un error al comunicarse con el servidor de análisis.');
        } finally {
            // Restaurar estado del botón
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
            btnSpinner.classList.add('hidden');
            btnText.innerText = 'Iniciar Procesamiento con IA';
            setTimeout(() => loadingStatus.classList.add('hidden'), 2000);
        }
    });

    function updateProgress(value, message) {
        progressBar.style.width = `${value}%`;
        progressPercentage.innerText = `${value}%`;
        if (message) statusMessage.innerHTML = message;
    }
</script>

</x-guest-layout>
