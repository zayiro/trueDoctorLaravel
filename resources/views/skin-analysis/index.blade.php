<x-guest-layout 
    :meta-title-medical-analysis="$meta_title_medicalAnalysis" 
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
    @if(session('error'))
        <div class="max-w-4xl mx-auto my-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-sm text-red-400 text-center">
            {{ session('error') }}
        </div>
    @endif
    <section class="bg-gradient-to-br from-rose-50 via-amber-50 to-orange-50 py-16 md:py-24 px-4 md:px-6">
        <div class="max-w-6xl mx-auto">
            
            <!-- Header -->
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-3">
                    <span class="bg-gradient-to-r from-orange-600 to-rose-600 bg-clip-text text-transparent">
                        Analiza tu Piel
                    </span>
                </h2>
                <h3 class="text-2xl md:text-3xl font-semibold text-gray-700 mb-3">Al Instante</h3>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    3 pasos simples para detectar cambios de piel potencialmente graves
                </p>
            </div>

            <!-- Steps Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                
                <!-- Step 1 -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gradient-to-br from-orange-100 to-amber-100 h-40 flex items-center justify-center relative">
                        <span class="absolute top-4 left-4 bg-gray-900 text-white text-sm font-bold px-4 py-1.5 rounded-full flex items-center gap-2">
                            Paso 1
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        
                        <!-- Camera Icon -->
                        <div class="flex items-center justify-center mt-3">
                            <svg class="w-24 h-24 text-orange-500 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h4 class="text-xl font-bold text-gray-900 mb-2">Toma una Foto</h4>
                        <p class="text-gray-600">
                            Sube una foto clara del área de piel que te preocupa
                        </p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gradient-to-br from-blue-100 to-cyan-100 h-40 flex items-center justify-center relative">
                        <span class="absolute top-4 left-4 bg-gray-900 text-white text-sm font-bold px-4 py-1.5 rounded-full flex items-center gap-2">
                            Paso 2
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        
                        <!-- AI Brain Icon -->
                        <div class="flex items-center justify-center mt-5">
                            <svg class="w-24 h-24 text-blue-500 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5h.01M9 9h.01" />
                            </svg>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h4 class="text-xl font-bold text-gray-900 mb-2">IA Analiza</h4>
                        <p class="text-gray-600">
                            Nuestra IA detecta signos tempranos de condiciones de piel
                        </p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gradient-to-br from-green-100 to-emerald-100 h-40 flex items-center justify-center relative">
                        <span class="absolute top-4 left-4 bg-gray-900 text-white text-sm font-bold px-4 py-1.5 rounded-full flex items-center gap-2">
                            Paso 3
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        
                        <!-- Report Icon -->
                        <div class="flex items-center justify-center mt-5">
                            <svg class="w-24 h-24 text-green-500 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h4 class="text-xl font-bold text-gray-900 mb-2">Recibe Resultado</h4>
                        <p class="text-gray-600">
                            Análisis detallado y recomendaciones de próximos pasos
                        </p>
                    </div>
                </div>

            </div>

            <!-- CTA Button -->
            <div class="flex justify-center mb-16">                
                <a href="{{ route('skin-analysis.upload') }}" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition duration-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.757l.108.464.464.108a1 1 0 010 1.942l-.464.108-.108.464a1 1 0 11-1.934-.484l.066-.284-.284-.066a1 1 0 010-1.942l.284-.066.066-.284A1 1 0 0112 2zm0 10a1 1 0 01.967.757l.108.464.464.108a1 1 0 010 1.942l-.464.108-.108.464a1 1 0 11-1.934-.484l.066-.284-.284-.066a1 1 0 010-1.942l.284-.066.066-.284A1 1 0 0112 12z" clip-rule="evenodd" />
                    </svg>
                    COMENZAR ANÁLISIS
                </a>
            </div>

            <!-- Benefits Section -->
            <div class="bg-white rounded-2xl shadow-lg p-8 md:p-10">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
                    Toma Control de tu Salud de Piel
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Benefit 1 -->
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-orange-100">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-1">⚡ Análisis Rápido</h4>
                            <p class="text-gray-600">
                                Resultado en minutos, no en horas
                            </p>
                        </div>
                    </div>

                    <!-- Benefit 2 -->
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-blue-100">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-1">62+ Condiciones Detectadas</h4>
                            <p class="text-gray-600">
                                Desde acné hasta melanoma
                            </p>
                        </div>
                    </div>

                    <!-- Benefit 3 -->
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-green-100">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-1">📊 Seguimiento Continuo</h4>
                            <p class="text-gray-600">
                                Monitorea el progreso de tu piel
                            </p>
                        </div>
                    </div>

                    <!-- Benefit 4 -->
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-purple-100">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-1">97% Validado Clínicamente</h4>
                            <p class="text-gray-600">
                                Confiado por dermatólogos certificados
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Bottom CTA -->
                <div class="mt-10 text-center">                    
                    <a href="{{ route('skin-analysis.upload') }}" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition duration-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.757l.108.464.464.108a1 1 0 010 1.942l-.464.108-.108.464a1 1 0 11-1.934-.484l.066-.284-.284-.066a1 1 0 010-1.942l.284-.066.066-.284A1 1 0 0112 2zm0 10a1 1 0 01.967.757l.108.464.464.108a1 1 0 010 1.942l-.464.108-.108.464a1 1 0 11-1.934-.484l.066-.284-.284-.066a1 1 0 010-1.942l.284-.066.066-.284A1 1 0 0112 12z" clip-rule="evenodd" />
                        </svg>
                        COMENZAR ANÁLISIS AHORA
                    </a>
                </div>
            </div>

        </div>
    </section>
</x-guest-layout>