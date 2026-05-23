@props(['status' => 'missing'])

<div class="max-w-7xl mx-auto my-8 bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8">
    
    {{-- CASO 1: Documentos en proceso de validación --}}
    @if($status === 'pending_validation')
        <div class="text-center py-8">
            <div class="mb-4">
                <img src="{{ asset('images/logoOpenDoctor.jpg') }}" 
                    alt="OpenDoctor" 
                    class="w-24 h-24 mx-auto rounded-full bg-white shadow-sm border border-slate-100 flex items-center justify-center p-3 overflow-hidden"
                >
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Documentación en proceso de validación</h3>
            <p class="text-sm text-slate-600 max-w-md mx-auto leading-relaxed">
                ¡Gracias por enviar tus soportes! Nuestro equipo está verificando tus credenciales con el registro nacional de salud. 
                <span class="block mt-2 font-medium text-amber-600">Este proceso toma menos de 24 horas hábiles.</span>                
            </p>
        </div>

    {{-- CASO 2: Hay que subir documentos --}}
    @else
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 text-blue-600 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Para activar tu cuenta debemos validar tu documentación</h2>
            <p class="text-sm text-slate-600 max-w-md mx-auto">
                En <span class="text-lg font-black text-slate-900 tracking-tight">
                            Open<span class="text-indigo-600">Doctor</span>
                        </span> garantizamos la seguridad de los pacientes. Tu perfil no será público hasta que verifiquemos tu identidad profesional.
            </p>

            @if($status === 'rejected')
                <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl flex items-center gap-2 justify-center">
                    <span>⚠️ <strong>Documentos previos rechazados:</strong> Por favor vuelve a subirlos asegurándote de que sean legibles.</span>
                </div>
            @endif
        </div>

        <!-- Formulario principal -->
        <form action="{{ route('partner.verify.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- SOLUCIÓN: Campo 1 con selectores directos inmunes a Livewire -->
                <div class="space-y-2 mb-3" x-data="{ 
                    fileName: '', 
                    isDragOver: false,
                    updateFile(files) {
                        if (files && files.length) {
                            this.fileName = files[0].name;
                        }
                    }
                }">
                    <label class="block text-sm font-medium text-slate-700">Cédula de Identidad (Frente/Anverso)</label>
                    
                    <div class="relative flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-xl transition bg-slate-50 group"
                         :class="{
                            'border-emerald-500 bg-emerald-50/40': fileName,
                            'border-blue-500 bg-blue-50/20': isDragOver && !fileName,
                            'border-slate-300 hover:border-blue-500': !fileName && !isDragOver
                         }"
                         @dragover.prevent="isDragOver = true"
                         @dragleave.prevent="isDragOver = false"
                         @drop.prevent="isDragOver = false; const input = document.getElementById('identity_card'); input.files = $event.dataTransfer.files; updateFile(input.files)">
                        
                        <!-- Usamos id único en vez de x-ref -->
                        <input type="file" 
                               id="identity_card_input"
                               name="identity_card" 
                               accept="image/jpeg,image/png,image/jpg,application/pdf" 
                               required 
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-30"
                               @change="updateFile($event.target.files)" />
                        
                        <!-- Estado: Archivo Seleccionado -->
                        <div x-show="fileName" x-cloak class="text-center px-4 flex flex-col items-center pointer-events-none z-20">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <span class="text-xs font-semibold text-slate-700 truncate max-w-[200px]" x-text="fileName"></span>
                            <span class="text-[10px] text-emerald-600 mt-1 font-medium">¡Listo para enviar! (Clic para cambiar)</span>
                        </div>

                        <!-- Estado: Vacío / Arrastrando -->
                        <div x-show="!fileName" class="text-center px-4 flex flex-col items-center pointer-events-none z-20">
                            <svg class="w-8 h-8 text-slate-400 group-hover:text-blue-500 mb-2 transition" :class="{'text-blue-500 scale-110': isDragOver}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span class="text-xs font-medium text-slate-600" x-text="isDragOver ? '¡Suéltalo aquí!' : 'Arrastra o selecciona tu cédula'"></span>
                            <span class="text-[10px] text-slate-400 mt-1">JPG, PNG o PDF hasta 4MB</span>
                        </div>
                    </div>
                    @error('identity_card') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- SOLUCIÓN: Campo 2 con selectores directos inmunes a Livewire -->
                <div class="space-y-2 mb-3" x-data="{ 
                    fileName: '', 
                    isDragOver: false,
                    updateFile(files) {
                        if (files && files.length) {
                            this.fileName = files[0].name;
                        }
                    }
                }">
                    <label class="block text-sm font-medium text-slate-700">Tarjeta o Licencia Profesional</label>
                    
                    <div class="relative flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-xl transition bg-slate-50 group"
                         :class="{
                            'border-emerald-500 bg-emerald-50/40': fileName,
                            'border-blue-500 bg-blue-50/20': isDragOver && !fileName,
                            'border-slate-300 hover:border-blue-500': !fileName && !isDragOver
                         }"
                         @dragover.prevent="isDragOver = true"
                         @dragleave.prevent="isDragOver = false"
                         @drop.prevent="isDragOver = false; const input = document.getElementById('professional_card_input'); input.files = $event.dataTransfer.files; updateFile(input.files)">
                        
                        <!-- Usamos id único en vez de x-ref -->
                        <input type="file" 
                               id="professional_card_input"
                               name="professional_card" 
                               accept="image/jpeg,image/png,image/jpg,application/pdf" 
                               required 
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-30"
                               @change="updateFile($event.target.files)" />
                        
                        <!-- Estado: Archivo Seleccionado -->
                        <div x-show="fileName" x-cloak class="text-center px-4 flex flex-col items-center pointer-events-none z-20">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <span class="text-xs font-semibold text-slate-700 truncate max-w-[200px]" x-text="fileName"></span>
                            <span class="text-[10px] text-emerald-600 mt-1 font-medium">¡Listo para enviar! (Clic para cambiar)</span>
                        </div>

                        <!-- Estado: Vacío / Arrastrando -->
                        <div x-show="!fileName" class="text-center px-4 flex flex-col items-center pointer-events-none z-20">
                            <svg class="w-8 h-8 text-slate-400 group-hover:text-blue-500 mb-2 transition" :class="{'text-blue-500 scale-110': isDragOver}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span class="text-xs font-medium text-slate-600" x-text="isDragOver ? '¡Suéltalo aquí!' : 'Arrastra o selecciona tu tarjeta'"></span>
                            <span class="text-[10px] text-slate-400 mt-1">JPG, PNG o PDF hasta 4MB</span>
                        </div>
                    </div>
                    @error('professional_card') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

            </div>

            <!-- Botón de Envío -->
            <button type="submit" class="w-full py-4 px-4 inline-flex justify-center items-center gap-x-2 text-lg font-semibold rounded-xl border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition shadow-sm">
                Enviar Documentación para Revisión
            </button>

            <p class="text-center text-[11px] text-slate-400 mt-4">
                🔒 Tus datos se encriptan de extremo a extremo y solo se usarán con fines de verificación legal.
            </p>
        </form>
    @endif
</div>
