<x-guest-layout>
    @if (session('info'))
        <div id="alert-info" class="flex items-center p-4 mb-4 text-info-800 rounded-2xl bg-info-50 border border-info-100 shadow-sm transition-opacity duration-500" role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
            </svg>
            <span class="sr-only">Éxito</span>
            <div class="ms-3 text-sm font-medium">
                {{ session('info') }}
            </div>
            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-info-50 text-info-500 rounded-lg focus:ring-2 focus:ring-info-400 p-1.5 hover:bg-info-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-info').remove()">
                <span class="sr-only">Cerrar</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>
    @endif
    <div class="min-h-screen bg-slate-50 flex items-center justify-center py-12 px-6">
        <div class="max-w-6xl w-full mt-3">
            
            <div class="text-center mb-12">
                <h2 class="text-5xl font-black text-slate-900 mb-4">Crear una cuenta gratuita</h2>
                <p class="text-lg text-slate-600 font-medium">Selecciona tu perfil para comenzar tu experiencia</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Opción: Paciente -->
                <a href="{{ route('register') }}" class="group bg-white p-8 rounded-3xl shadow-sm border-2 border-transparent hover:border-blue-500 hover:shadow-xl transition-all duration-300 text-center">
                    <div class="bg-blue-100 text-blue-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Soy Paciente</h3>
                    <p class="text-sm text-slate-500">Busca doctores, agenda citas y gestiona tu historial médico.</p>
                </a>

                <!-- Opción: Especialista -->
                <a href="{{ route('partner.register') }}" class="group bg-white p-8 rounded-3xl shadow-sm border-2 border-transparent hover:border-blue-500 hover:shadow-xl transition-all duration-300 text-center">
                    <div class="bg-emerald-100 text-emerald-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Soy Especialista</h3>
                    <p class="text-sm text-slate-500">Gestiona tu agenda, sedes y atiende a tus pacientes digitalmente.</p>
                </a>

                <!-- Opción: Clínica -->
                <!--
                <a href="{{ route('clinic.register') }}" class="group bg-white p-8 rounded-3xl shadow-sm border-2 border-transparent hover:border-blue-500 hover:shadow-xl transition-all duration-300 text-center">
                    <div class="bg-purple-100 text-purple-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Soy Gerente</h3>
                    <p class="text-sm text-slate-500">Administra múltiples doctores, sedes y servicios hospitalarios.</p>
                </a>-->

            </div>

            <div class="mt-6 text-center">
                <p class="text-slate-500">¿Ya tienes una cuenta? <a href="{{ route('login') }}" class="text-blue-600 font-bold hover:underline">Inicia sesión</a></p>
            </div>
        </div>
    </div>
</x-guest-layout>
