<x-guest-layout
    meta_title="Perfil de Doctor o Centro Médico | OpenDoctor"
    meta_description="Perfil Médico especializado"
>

    <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 font-sans my-6">
    <div class="grid grid-cols-1 md:grid-cols-12">
        
        {{-- COLUMNA IZQUIERDA: Imagen y Biografía --}}
        <div class="md:col-span-5 flex flex-col bg-gray-50/50">
            {{-- Contenedor de la Imagen con Distintivo Premium/VIP --}}
            <div class="relative w-full aspect-square bg-gray-200">
                <img 
                    src="{{ $doctor->avatar_url ?? asset('images/default-doctor.png') }}" 
                    alt="{{ $doctor->user->name }}" 
                    class="w-full h-full object-cover object-top"
                >
                {{-- Icono de Corona Corona/Verificado (Esquina superior izquierda) --}}
                @if($doctor->reviews_count > 10 && $doctor->rating >= 4.8)
                    <div class="absolute top-4 left-4 bg-amber-500 text-white p-2 rounded-xl shadow-lg ring-2 ring-white/50">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a1 1 0 01-1.316.949l-4.21-1.403a3 3 0 00-1.948 0l-4.21 1.403A1 1 0 013 16V5zm3 4a1 1 0 000 2h6a1 1 0 100-2H7zm0 4a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Bloque de Identificación Inferior Izquierdo --}}
            <div class="bg-sky-600 text-white px-6 py-5 shadow-inner">
                <h1 class="text-xl font-bold tracking-tight leading-tight">Dr. {{ $doctor->user->name }}</h1>
                <p class="text-sky-100 text-xs font-medium mt-1 tracking-wide uppercase">{{ $doctor->specialties->first()->name ?? 'Especialista Médico' }}</p>
            </div>

            {{-- Sección: Biografía --}}
            <div class="p-6 flex-1 bg-white md:bg-transparent">
                <h3 class="text-xs font-bold text-sky-600 uppercase tracking-wider mb-2">Biografía</h3>
                <div class="text-xs text-gray-600 leading-relaxed space-y-3">
                    <p>{{ $doctor->bio ?? 'Sin descripción profesional disponible en este momento.' }}</p>
                    @if($doctor->experience_years)
                        <p class="text-[11px] text-gray-500 italic">Formado con los más altos estándares científicos y éticos en salud.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA: Detalles Profesionales y Contacto --}}
        <div class="md:col-span-7 p-6 md:p-8 flex flex-col justify-between bg-white space-y-6">
            
            {{-- Bloque de Perfil / Hospital --}}
            <div>
                <h3 class="text-xs font-bold text-sky-600 uppercase tracking-wider mb-1">Perfil Profesional</h3>
                <h2 class="text-base font-bold text-gray-800">Dr. {{ $doctor->user->name }}</h2>
                <p class="text-xs text-gray-500 mt-0.5 font-medium">{{ $doctor->specialties->first()->name ?? 'Especialista' }}</p>
                
                {{-- Credenciales Académicas --}}
                <div class="mt-3 text-xs text-gray-600 space-y-1 bg-slate-50 p-3 rounded-xl border border-slate-100/80">
                    <p class="font-medium text-gray-700">Licencia Médica: <span class="font-normal text-gray-600">{{ $doctor->medical_license ?? 'En proceso de verificación' }}</span></p>
                    <p class="font-medium text-gray-700">Institución: <span class="font-normal text-gray-600">{{ $doctor->clinic?->user->name ?? 'Consultorio Independiente' }}</span></p>
                </div>
            </div>

            {{-- Sección: Especialidades / Enfoques (Badges) --}}
            <div>
                <h3 class="text-xs font-bold text-sky-600 uppercase tracking-wider mb-2.5">Especialidades</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($doctor->specialties as $specialty)
                        <span class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 text-[11px] font-medium rounded-full shadow-xs">
                            {{ $specialty->name }}
                        </span>
                    @empty
                        <span class="px-3 py-1.5 bg-white border border-slate-200 text-slate-500 text-[11px] font-medium rounded-full shadow-xs">
                            Medicina General
                        </span>
                    @endforelse
                </div>
            </div>

            {{-- Fila de Datos Rápidos: Experiencia, Idiomas, Tipo --}}
            <div class="grid grid-cols-3 gap-4 border-t border-b border-gray-100 py-4 text-xs">
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Experiencia</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">{{ $doctor->experience_years ?? '0' }} Años+</p>
                </div>
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Idiomas</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">
                        {{ is_array($doctor->languages) ? implode(', ', $doctor->languages) : 'Español' }}
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Vinculación</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">{{ $doctor->clinic_id ? 'Staff de Clínica' : 'Médico Autónomo' }}</p>
                </div>
            </div>

            {{-- Sección Inferior: Datos de Contacto --}}
            <div class="pt-2">
                <h3 class="text-xs font-bold text-sky-600 uppercase tracking-wider mb-3">Contacto</h3>
                <div class="space-y-2.5 text-xs text-gray-600">
                    {{-- Teléfonos --}}
                    @if($doctor->phone)
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h32.83a1 1 0 01.9.553l2 4a1 1 0 01-.145 1.055L17 14l-2 2a11.042 11.042 0 01-5.5-5.5l2-2 2.943-2.943a1 1 0 011.055-.145l4 2a1 1 0 01.553.9v.583a2 2 0 01-2 2H3z" />
                            </svg>
                            <span class="font-medium tracking-wide">{{ $doctor->phone }}</span>
                        </div>
                    @endif

                    {{-- Correo / Web --}}
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="text-slate-600">{{ $doctor->user->email }}</span>
                    </div>

                    {{-- Dirección Física Estilizada con Línea de Borde Izquierdo --}}
                    @if($doctor->address)
                        <div class="mt-4 pl-3.5 border-l-2 border-sky-500 py-0.5 space-y-0.5">
                            <p class="font-bold text-gray-800 text-[11px] uppercase tracking-wide">Ubicación del Consultorio</p>
                            <p class="text-gray-600 leading-tight">{{ $doctor->address->address }}</p>
                            <p class="text-[11px] text-gray-400 font-medium">{{ $doctor->address->name }} - Cali, Colombia</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>


</x-guest-layout>    
