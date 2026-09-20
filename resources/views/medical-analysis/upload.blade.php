<x-guest-layout 
    :meta-title-medical-analysis="$meta_title_medicalAnalysis" 
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
    <!-- MOSTRAR ERRORES Y MENSAJES -->
    @if ($errors->any())
        <div class="max-w-7xl w-full mx-auto px-6 py-4 mt-3">
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 space-y-2">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-400 font-bold">Errores de validación:</span>
                </div>
                <ul class="text-red-300 text-sm space-y-1 ml-7">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-7xl w-full mx-auto px-6 py-4 mt-3">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">                
                <span class="block sm:inline">{{ session('error') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="max-w-7xl w-full mx-auto px-6 py-4">
            <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-green-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-green-200 font-bold">✓ {{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-7xl w-full mx-auto px-6 py-12 flex-grow mt-6">
        <div class="bg-slate-950 rounded-2xl border border-white/10 p-8 shadow-2xl space-y-6">
            
            <!-- ENCABEZADO -->
            <div class="space-y-2">
                <h1 class="text-2xl font-black text-white">Análisis Clínico Avanzado por IA</h1>
                <p class="text-md text-slate-400">Arrastra o selecciona informes médicos (PDF, JPG, PNG, DICOM) y obtén una interpretación guiada por Inteligencia Artificial.</p>
            </div>

            <!-- 🆕 TABLA DE PRECIOS FIJOS (visible desde el inicio) -->
            <!--
            <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-6">
                <h3 class="text-white font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    Precios Fijos por Tipo de Examen
                </h3>
                
                <div class="grid md:grid-cols-2 gap-3">
                    <div class="flex justify-between items-center p-3 bg-slate-900/50 rounded border border-white/5 hover:border-blue-500/30 transition">
                        <span class="text-slate-300">🧪 Exámenes de Laboratorio</span>
                        <span class="font-bold text-blue-400">${{ number_format($prices['lab'], 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-slate-900/50 rounded border border-white/5 hover:border-blue-500/30 transition">
                        <span class="text-slate-300">📸 Radiografía</span>
                        <span class="font-bold text-blue-400">${{ number_format($prices['xray'], 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-slate-900/50 rounded border border-white/5 hover:border-blue-500/30 transition">
                        <span class="text-slate-300">🔊 Ecografía</span>
                        <span class="font-bold text-blue-400">${{ number_format($prices['ultrasound'], 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-slate-900/50 rounded border border-white/5 hover:border-blue-500/30 transition">
                        <span class="text-slate-300">📊 Tomografía</span>
                        <span class="font-bold text-blue-400">${{ number_format($prices['ct'], 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-slate-900/50 rounded border border-white/5 hover:border-blue-500/30 transition">
                        <span class="text-slate-300">🧠 Resonancia</span>
                        <span class="font-bold text-blue-400">${{ number_format($prices['mri'], 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-slate-900/50 rounded border border-white/5 hover:border-blue-500/30 transition">
                        <span class="text-slate-300">💾 DICOM</span>
                        <span class="font-bold text-blue-400">${{ number_format($prices['dicom'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            -->

            <!-- FORMULARIO -->
            <form id="uploadForm" enctype="multipart/form-data" class="space-y-6">            
                @csrf
                <!-- ZONA DROPZONE -->
                <div id="dropzone" class="border-2 border-dashed border-white/20 hover:border-blue-500/50 bg-slate-800 rounded-xl p-8 text-center cursor-pointer transition-all relative">
                    <input type="file" name="medical_files[]" id="medical_files" class="hidden" multiple accept=".pdf,.jpg,.jpeg,.png,.dcm,.dicom">
                    <input type="hidden" name="detected_exam_type" id="detected_exam_type">

                    <div class="space-y-3 pointer-events-none" id="dropzonePrompt">
                        <div class="w-12 h-12 rounded-full bg-slate-600 text-blue-400 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                            </svg>
                        </div>
                        <p class="font-semibold text-slate-200">Haz clic para buscar o suelta tus archivos aquí</p>
                        <p class="text-slate-500 text-sm">PDF, JPG, PNG o DICOM - Máximo 5 archivos, 10MB cada uno</p>
                    </div>

                    <div id="fileList" class="hidden text-left bg-slate-950/80 border border-white/5 p-4 rounded-xl space-y-2 text-xs"></div>
                </div>

                <!-- 🆕 ALERTA DE TIPO DETECTADO (oculta al inicio) -->
                <div id="examDetectionAlert" class="hidden bg-green-500/10 border border-green-500/30 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-white font-semibold text-lg" id="detectedExamLabel"></p>
                            <p class="text-green-400 text-sm" id="detectedFileCount"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-slate-400 text-xs">Costo total:</p>
                            <p class="text-3xl font-bold text-green-400" id="detectedPrice"></p>
                        </div>
                    </div>
                </div>

                <!-- DATOS DEL PACIENTE -->
                <div class="grid md:grid-cols-2 gap-6 text-left">
                    <!-- Email -->
                    <div class="space-y-1.5">
                        <label for="customer_email" class="text-sm font-bold tracking-wider text-white">¿A qué correo electrónico enviamos tu resultado?</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615m19.5 0A2.25 2.25 0 0 0 19.5 4.5" />
                                </svg>
                            </span>
                            <input type="email" name="customer_email" id="customer_email" required 
                                value="{{ auth()->check() ? auth()->user()->email : old('customer_email') }}" 
                                placeholder="tu-cuenta@correo.com" 
                                autocomplete="off" 
                                class="w-full pl-10 p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-slate-500 shadow-sm">
                        </div>
                        <p class="text-sm text-slate-400 font-medium">Te enviaremos el resultado digital a esta dirección</p>
                    </div>

                    <!-- Idioma -->
                    <div class="space-y-1.5">
                        <label for="selected_language" class="text-sm font-bold tracking-wider text-white">¿En qué idioma quieres ver el resultado?</label>
                        <select name="selected_language" id="selected_language" required class="w-full p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                            <option value="es" class="bg-slate-950" selected>Español (Spanish)</option>
                            <option value="en" class="bg-slate-950">Inglés (English)</option>
                            <option value="fr" class="bg-slate-950">Frances (French)</option>
                            <option value="pt" class="bg-slate-950">Portugues (Portuguese)</option>
                            <option value="de" class="bg-slate-950">Alemán (Deutsch)</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <h2 class="text-xl font-black text-white">Estos datos aumentan la exactitud del informe</h2>
                    </div>

                    <!-- Motivo -->
                    <div class="space-y-1.5">
                        <label for="reason_type" class="text-sm font-bold tracking-wider text-white">¿Cuál es el motivo de estos exámenes?</label>
                        <select name="reason_type" id="reason_type" required class="w-full p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                            <option value="routine" class="bg-slate-950">Control de rutina anual o chequeo preventivo.</option>
                            <option value="monitoring" class="bg-slate-950">Seguimiento continuo de una patología médica existente.</option>
                            <option value="symptoms" class="bg-slate-950">Evaluación motivada por sintomatología reciente del paciente.</option>
                            <option value="other" class="bg-slate-950">Motivos complementarios</option>
                        </select>
                    </div>

                    <!-- Detalles -->
                    <div class="space-y-1.5">
                        <label for="reason_custom" class="text-sm font-bold tracking-wider text-white">Detalles o síntomas adicionales (Opcional)</label>
                        <textarea name="reason_custom" id="reason_custom" rows="1" placeholder="Ej: Sufro de colesterol alto..." class="w-full p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm placeholder:text-slate-500 resize-none"></textarea>
                    </div>                                                   
                </div>

                <!-- CÓDIGO PROMO Y BOTÓN -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-left">                                        
                    <div class="flex flex-col pt-6 space-y-1.5 min-w-0">
                        <button type="submit" id="submitBtn" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3.5 px-2 md:px-3 rounded-xl transition-all shadow-lg shadow-blue-500/10 flex items-center justify-center gap-2">
                            <span id="btnText">Enviar Información</span>
                            <svg id="btnSpinner" class="animate-spin h-5 w-5 text-white hidden" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                        <small class="text-sm text-slate-400 font-medium px-1">Al hacer clic, aceptas nuestros <a href="{{ route('terms.show') }}" class="underline hover:text-indigo-600 transition-colors">Términos de Servicio</a> y <a href="{{ route('privacy.show') }}" class="underline hover:text-indigo-600 transition-colors">Política de Privacidad</a>.</small>
                    </div>                    
                </div>
            </form>

            <!-- ESTADO DE CARGA -->
            <div id="loadingStatus" class="hidden bg-slate-900 border border-blue-500/20 rounded-xl p-6 space-y-4">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-white font-semibold" id="statusMessage"><i class="fa-solid fa-shield-halved mr-1"></i> Anonimizando documentos...</span>
                    <span class="text-white font-bold" id="progressPercentage">0%</span>
                </div>
                <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-white/5">
                    <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-emerald-500 text-white h-full w-0 transition-all duration-300"></div>
                </div>
            </div>          
        </div>
    </div>

    <div class="border-t border-white/5 py-4 text-center text-[12px] text-slate-600 mb-5">
        <p>openDoctorOnline AI. Procesamiento asíncrono seguro y cifrado de punta a punta.</p>
    </div>

    <script>
    /**
     * MedicalFileValidator - Validador de archivos médicos para OpenDoctor
     */
    class MedicalFileValidator {
        constructor() {
            this.MAX_FILE_SIZE = 10 * 1024 * 1024;
            this.MAX_FILES = 5;
            this.ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'dcm', 'dicom'];
            this.ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/dicom', 'image/dicom'];
        }

        async validateFiles(files) {
            const result = { valid: true, validFiles: [], rejectedFiles: [] };
            if (files.length > this.MAX_FILES) {
                result.valid = false;
                result.rejectedFiles.push({ name: 'Cantidad de archivos', errors: [`Máximo ${this.MAX_FILES} archivos permitidos. Seleccionaste ${files.length}.`] });
                return result;
            }
            for (let file of files) {
                const validation = this.validateFile(file);
                if (validation.valid) {
                    result.validFiles.push({ file: file, warnings: validation.warnings || [] });
                } else {
                    result.rejectedFiles.push({ name: file.name, errors: validation.errors || [] });
                    result.valid = false;
                }
            }
            return result;
        }

        validateFile(file) {
            const result = { valid: true, errors: [], warnings: [] };
            const extension = this.getFileExtension(file.name).toLowerCase();
            if (!this.ALLOWED_EXTENSIONS.includes(extension)) {
                result.valid = false;
                result.errors.push(`Formato no permitido. Solo se aceptan: ${this.ALLOWED_EXTENSIONS.join(', ').toUpperCase()}`);
            }
            if (!this.isMimeTypeAllowed(file.type)) {
                result.warnings.push('El tipo MIME no se reconoce completamente, pero continuaremos.');
            }
            if (file.size > this.MAX_FILE_SIZE) {
                result.valid = false;
                const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
                result.errors.push(`Archivo muy grande (${sizeInMB}MB). Máximo permitido: 10MB`);
            }
            if (file.size < 1024) {
                result.valid = false;
                result.errors.push('El archivo es demasiado pequeño o está vacío.');
            }
            if (extension === 'pdf' && file.size > 5 * 1024 * 1024) {
                result.warnings.push('PDF grande: puede tardar más en procesar');
            }
            return result;
        }

        getFileExtension(filename) {
            return filename.split('.').pop() || '';
        }

        isMimeTypeAllowed(mimeType) {
            if (!mimeType) return false;
            return this.ALLOWED_MIME_TYPES.some(allowed => mimeType.toLowerCase().includes(allowed.toLowerCase()));
        }

        detectExamType(filename) {
            const name = filename.toLowerCase();
            if (['radiografia', 'radiografía', 'rx', 'xray', 'x-ray'].some(k => name.includes(k))) return 'xray';
            if (['tomografia', 'tomografía', 'ct', 'tac', 'scan'].some(k => name.includes(k))) return 'ct';
            if (['resonancia', 'mri', 'rmn'].some(k => name.includes(k))) return 'mri';
            if (['ecografia', 'ecografía', 'ultrasound', 'eco'].some(k => name.includes(k))) return 'ultrasound';
            if (['mamografia', 'mamografía'].some(k => name.includes(k))) return 'mammography';
            if (['dicom', 'dcm'].some(k => name.includes(k))) return 'dicom';
            return 'lab';
        }
    }

    // Resto del código inicia aquí
    const EXAM_PRICES = {!! json_encode($prices) !!};
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('medical_files');
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
    const examDetectionAlert = document.getElementById('examDetectionAlert');
    const detectedExamLabel = document.getElementById('detectedExamLabel');
    const detectedFileCount = document.getElementById('detectedFileCount');
    const detectedPrice = document.getElementById('detectedPrice');
    const detectedExamTypeInput = document.getElementById('detected_exam_type');

    const validator = new MedicalFileValidator();

    dropzone.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('border-blue-500', 'bg-blue-500/5'); });
    dropzone.addEventListener('dragleave', () => { dropzone.classList.remove('border-blue-500', 'bg-blue-500/5'); });
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-blue-500', 'bg-blue-500/5');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelection();
        }
    });

    fileInput.addEventListener('change', handleFileSelection);

    async function handleFileSelection() {
        const validation = await validator.validateFiles(fileInput.files);

        if (!validation.valid && validation.validFiles.length === 0) {
            showErrorModal(validation.rejectedFiles);
            fileInput.value = '';
            fileList.classList.add('hidden');
            dropzonePrompt.classList.remove('hidden');
            examDetectionAlert.classList.add('hidden');
            return;
        }

        if (validation.rejectedFiles.length > 0) {
            showRejectedFilesNotification(validation.rejectedFiles);
        }

        if (validation.validFiles.length > 0) {
            const detectedType = detectExamType(validation.validFiles[0].file.name);
            const price = EXAM_PRICES[detectedType] || 30000;
            //showExamDetection(detectedType, price, validation.validFiles);
            detectedExamTypeInput.value = detectedType;
        }

        updateFileList(validation.validFiles);
    }

    function detectExamType(fileName) {
        return validator.detectExamType(fileName);
    }

    function showExamDetection(type, price, files) {
        const typeLabels = {
            'lab': '🧪 Análisis de Laboratorio',
            'xray': '📸 Radiografía',
            'ultrasound': '🔊 Ecografía',
            'ct': '📊 Tomografía',
            'mri': '🧠 Resonancia Magnética',
            'mammography': '🎀 Mamografía',
            'dicom': '💾 Estudio DICOM'
        };
        detectedExamLabel.textContent = typeLabels[type];
        detectedFileCount.textContent = `${files.length} archivo(s) listo(s) para procesar`;
        detectedPrice.textContent = `$${price.toLocaleString('es-CO')}`;
        examDetectionAlert.classList.remove('hidden');
    }

    function showRejectedFilesNotification(rejectedFiles) {
        const existing = document.querySelector('.rejected-files-notification');
        if (existing) existing.remove();
        const notification = document.createElement('div');
        notification.className = 'rejected-files-notification bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 mb-4 space-y-3';
        const rejectedHtml = rejectedFiles.map(f => `<div class="flex items-start gap-2"><svg class="w-5 h-5 text-yellow-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><div class="flex-1"><p class="text-yellow-200 text-sm font-semibold">${f.name}</p><ul class="text-yellow-100 text-xs mt-1 space-y-1">${f.errors.map(err => `<li>• ${err}</li>`).join('')}</ul></div></div>`).join('');
        notification.innerHTML = `<div class="flex items-start justify-between gap-4"><div class="flex-1"><p class="text-yellow-200 font-semibold mb-3">⚠️ ${rejectedFiles.length} archivo(s) rechazado(s)</p><div class="space-y-3">${rejectedHtml}</div></div><button onclick="this.closest('.rejected-files-notification').remove()" class="text-yellow-400 hover:text-yellow-300 shrink-0"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></button></div>`;
        dropzone.after(notification);
    }

    function showErrorModal(rejectedFiles) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
        const rejectedHtml = rejectedFiles.map(f => `<div class="flex items-start gap-3 p-3 bg-red-500/10 border border-red-500/30 rounded-lg"><svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg><div class="flex-1"><p class="font-semibold text-red-600 text-sm">${f.name}</p><ul class="text-red-500 text-xs mt-1 space-y-1">${f.errors.map(err => `<li>• ${err}</li>`).join('')}</ul></div></div>`).join('');
        modal.innerHTML = `<div class="bg-slate-950 border border-red-500/30 rounded-2xl p-8 max-w-md w-full space-y-4"><div class="flex items-center gap-3"><svg class="w-8 h-8 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg><h2 class="text-xl font-bold text-white">No se pudieró
        n procesar los archivos</h2></div><div class="space-y-2">${rejectedHtml}</div><button onclick="this.closest('.fixed').remove()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">Intentar de nuevo</button></div>`;
        document.body.appendChild(modal);
    }

    function updateFileList(validFiles) {
        fileList.innerHTML = '<p class="font-semibold text-slate-300 mb-3">✅ Archivos listos para procesar</p>';
        validFiles.forEach((item) => {
            const file = item.file;
            const fileExt = file.name.split('.').pop().toLowerCase();
            let icon = '📄';
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) icon = '🖼️';
            else if (['dcm', 'dicom'].includes(fileExt)) icon = '🩻';
            else if (fileExt === 'pdf') icon = '📕';
            let warningHtml = '';
            if (item.warnings && item.warnings.length > 0) {
                warningHtml = `<div class="text-xs text-blue-400 mt-1 ml-6">${item.warnings.map(w => `ℹ️ ${w}`).join('<br>')}</div>`;
            }
            fileList.innerHTML += `<div class="flex items-start gap-3 p-3 bg-slate-900/50 border border-green-500/30 rounded-lg mb-2"><span class="text-lg shrink-0">${icon}</span><div class="flex-1 min-w-0"><p class="font-semibold text-green-400 text-sm truncate">${file.name}</p><p class="text-xs text-slate-400">${(file.size / 1024 / 1024).toFixed(2)} MB</p>${warningHtml}</div></div>`;
        });
        fileList.classList.remove('hidden');
        dropzonePrompt.classList.add('hidden');
    }

    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!fileInput.files.length) { alert('Por favor, selecciona al menos un archivo.'); return; }
        if (!detectedExamTypeInput.value) { alert('No se pudo detectar el tipo de examen. Intenta de nuevo.'); return; }
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
        btnSpinner.classList.remove('hidden');
        btnText.innerText = 'Procesando Documentos...';
        loadingStatus.classList.remove('hidden');
        updateProgress(35, '<i class="fa-solid fa-user-shield mr-1 text-white"></i> Anonimizando de forma segura...');
        const formData = new FormData(uploadForm);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        try {
            // 1. Iniciamos el progreso inmediatamente antes del fetch
            updateProgress(65, '<i class="fa-solid fa-brain mr-1 text-white"></i> Preparando vista previa...');

            const response = await fetch("{{ route('medical-analysis.before-preview') }}", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": csrfToken },
                body: formData
            });
           
            if (!response.ok) throw new Error('Error en el servidor médico.');
                       
            const data = await response.json();

             console.log(data);
            
            if (data.status === 'success') {
                // 2. Transición inmediata al éxito
                updateProgress(100, '<i class="fa-solid fa-check text-white mr-1"></i> Análisis Completado.');
                
                // 3. Espera de 1.5 segundos antes de redirigir
                setTimeout(() => { window.location.href = data.redirect_url; }, 1500);
            } else {
                alert('No se pudo completar el análisis del reporte.');
            }
        } catch (error) {
            console.error("Detalle del error:", error);
            alert('Ocurrió un error al comunicarse con el servidor de análisis.');
        } finally {
            // 4. Restauramos el botón de inmediato
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
            btnSpinner.classList.add('hidden');
            btnText.innerText = 'Analizar Información Médica';
            
            // 5. Ocultamos el contenedor de carga un poco después para que se aprecie el éxito/error
            setTimeout(() => loadingStatus.classList.add('hidden'), 2000);
        }

    });

    function updateProgress(value, message) {
        progressBar.style.width = `${value}%`;
        progressPercentage.innerText = `${value}%`;
        if (message) statusMessage.innerHTML = message;
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof gtag === 'function') {
            gtag('event', 'view_medical_analysis_upload', { 'page_type': 'upload_form', 'feature_type': 'mixed_analysis' });
        }
    });

    fileInput.addEventListener('change', () => {
        if (typeof gtag === 'function' && fileInput.files.length > 0) {
            gtag('event', 'select_medical_files', { 'file_count': fileInput.files.length, 'detected_type': detectedExamTypeInput.value || 'unknown' });
        }
    });

    uploadForm.addEventListener('submit', () => {
        if (typeof gtag === 'function') {
            gtag('event', 'begin_medical_analysis', { 'exam_type': detectedExamTypeInput.value, 'file_count': fileInput.files.length, 'language': document.querySelector('select[name="selected_language"]').value });
        }
    });
</script>
</x-guest-layout>