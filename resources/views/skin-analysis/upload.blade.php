<x-guest-layout>
    @if(session('error'))
        <div class="max-w-4xl mx-auto my-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-sm text-red-400 text-center">
            {{ session('error') }}
        </div>
    @endif

    <div class="min-h-screen bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 py-12 px-4 md:px-6">
        <div class="max-w-4xl mx-auto">
            
            <!-- Header Section -->
            <div class="text-center mb-16">
                <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-4">
                    Obtén un Resultado Instantáneo
                </h1>
                <p class="text-xl text-gray-700 mb-2">
                    Analiza tu piel en segundos: <span class="font-bold">Toma o sube una foto</span>
                </p>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-12">
                
                <!-- Left: Image & Features -->
                <div>
                    <!-- Placeholder Image -->
                    <div class="mb-8">
                        <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=500&h=600&fit=crop" alt="Análisis de piel" class="rounded-3xl shadow-2xl w-full object-cover h-96">
                    </div>

                    <!-- Features List -->
                    <div class="space-y-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Mira la Diferencia:</h3>
                        
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-bold text-gray-900">Detecta <span class="text-purple-600">62+ condiciones de piel</span></p>
                                <p class="text-sm text-gray-600">Incluyendo melanoma</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-bold text-gray-900"><span class="text-purple-600">97%+</span> Precisión de Grado Clínico</p>
                                <p class="text-sm text-gray-600">Validado por dermatólogos</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-bold text-gray-900">Totalmente <span class="text-purple-600">Privado</span> y Anónimo</p>
                                <p class="text-sm text-gray-600">Sin necesidad de crear cuenta</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-bold text-gray-900">Resultado en <span class="text-purple-600">1 Minuto</span></p>
                                <p class="text-sm text-gray-600">Análisis inmediato con IA</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Form -->
                <div>
                    <form action="{{ route('skin-analysis.before-preview') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl shadow-2xl p-8 md:p-10">
                        @csrf

                        <!-- Error Messages -->
                        @if ($errors->any())
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                                <ul class="text-red-700 text-sm space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                                <p class="text-red-700">{{ session('error') }}</p>
                            </div>
                        @endif

                        <!-- File Upload -->
                        <div class="mb-6">
                            <label class="block text-lg font-bold text-gray-900 mb-3">
                                📸 Sube tu Foto
                            </label>

                            <div id="drop-zone" class="border-2 border-dashed border-purple-300 rounded-2xl p-8 text-center bg-purple-50 cursor-pointer hover:border-purple-500 hover:bg-purple-100 transition duration-300">
                                <svg class="mx-auto h-12 w-12 text-purple-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <p class="text-gray-700 font-semibold mb-1">Arrastra tu foto aquí</p>
                                <p class="text-sm text-gray-600">O haz clic para seleccionar</p>
                                <p class="text-xs text-gray-500 mt-2">Máx 10 MB • JPG, PNG, WebP</p>
                            </div>

                            <input type="file" name="files[]" id="files" multiple accept="image/*" class="hidden" required>

                            <div id="file-preview" class="mt-4 grid grid-cols-2 gap-3">
                                <!-- Previews aquí -->
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-6">
                            <label for="email" class="block text-lg font-bold text-gray-900 mb-2">
                                📧 Tu Email
                            </label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                placeholder="tu@email.com"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                required
                                value="{{ old('email') }}"
                            >
                            <p class="text-xs text-gray-600 mt-1">Recibirás el análisis en tu correo</p>
                        </div>

                        <!-- Language -->
                        <div class="mb-6">
                            <label for="language" class="block text-lg font-bold text-gray-900 mb-2">
                                🌐 Idioma
                            </label>
                            <select name="language" id="language" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="es">Español</option>
                                <option value="en">English</option>
                            </select>
                        </div>

                        <!-- Body Location (Optional) -->
                        <div class="mb-6">
                            <label for="body_location" class="block text-lg font-bold text-gray-900 mb-2">
                                📍 Ubicación (Opcional)
                            </label>
                            <input
                                type="text"
                                name="body_location"
                                id="body_location"
                                placeholder="Ej: brazo derecho, zona genital"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                value="{{ old('body_location') }}"
                            >
                        </div>

                        <!-- Description (Optional) -->
                        <div class="mb-8">
                            <label for="description" class="block text-lg font-bold text-gray-900 mb-2">
                                📝 Descripción (Opcional)
                            </label>
                            <textarea
                                name="description"
                                id="description"
                                rows="3"
                                placeholder="Lleva 2 semanas, pica, empeora con sudor..."
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
                            >{{ old('description') }}</textarea>
                        </div>

                        <!-- Disclaimer -->
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8 rounded">
                            <p class="text-sm text-yellow-800">
                                <strong>⚠️ Importante:</strong> Este análisis es preliminar educativo, NO un diagnóstico médico. Siempre consulta a un profesional.
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            id="submit-btn"
                            class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition duration-200 text-lg shadow-lg"
                            disabled
                        >
                            <span id="btn-text">➕ Continuar al Resumen</span>
                            <svg id="btn-spinner" class="hidden h-5 w-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4a8 8 0 018 8" />
                            </svg>
                        </button>
                    </form>
                </div>

            </div>

            <!-- Trust Badges -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-16">
                <div class="text-center">
                    <p class="text-4xl font-bold text-purple-600">+200k</p>
                    <p class="text-sm text-gray-600">Análisis Realizados</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-bold text-purple-600">97%</p>
                    <p class="text-sm text-gray-600">Precisión</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-bold text-purple-600">24/7</p>
                    <p class="text-sm text-gray-600">Disponible</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-bold text-purple-600">🔒</p>
                    <p class="text-sm text-gray-600">100% Privado</p>
                </div>
            </div>

        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('files');
        const filePreview = document.getElementById('file-preview');
        const submitBtn = document.getElementById('submit-btn');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-purple-500', 'bg-purple-100');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-purple-500', 'bg-purple-100');
            });
        });

        dropZone.addEventListener('drop', handleDrop, false);
        fileInput.addEventListener('change', handleFiles, false);

        function handleDrop(e) {
            let dt = e.dataTransfer;
            let files = dt.files;
            fileInput.files = files;
            handleFiles();
        }

        function handleFiles() {
            const files = fileInput.files;
            filePreview.innerHTML = '';

            if (files.length === 0) {
                submitBtn.disabled = true;
                return;
            }

            if (files.length > 3) {
                alert('Máximo 3 fotos');
                fileInput.value = '';
                submitBtn.disabled = true;
                return;
            }

            Array.from(files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" class="w-full h-24 object-cover rounded-lg border-2 border-purple-200">
                        <span class="absolute top-1 right-1 bg-purple-600 text-white text-xs px-2 py-1 rounded">${file.name}</span>
                    `;
                    filePreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });

            submitBtn.disabled = false;
        }
    });
    </script>
</x-guest-layout>    