<x-app-layout>
    <div class="max-w-3xl mx-auto py-12 px-4">
        <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
            <!-- Encabezado -->
            <div class="bg-blue-600 p-8 text-white text-center">
                <h2 class="text-2xl font-black italic">Resumen de tu Cita</h2>
                <p class="opacity-90">Verifica los datos antes de confirmar</p>
            </div>

            <form action="{{ route('appointments.book') }}" method="POST" class="p-8 space-y-8">
                @csrf
                <!-- Datos ocultos para procesar el guardado final -->
                <input type="hidden" name="service_id" value="{{ $service->id }}">
                <input type="hidden" name="address_id" value="{{ $address?->id }}">
                <input type="hidden" name="datetime" value="{{ $datetime }}">
                <input type="hidden" name="notes" value="{{ $notes }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Sección 1: Profesional y Servicio -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <img src="{{ $service->doctor->user->profile_photo_url }}" class="w-16 h-16 rounded-full border-2 border-blue-100">
                            <div>
                                <p class="text-xs font-bold text-blue-600 uppercase">Especialista</p>
                                <p class="font-bold text-gray-900">{{ $service->doctor->user->name }}</p>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <p class="text-xs font-bold text-gray-500 uppercase">Servicio</p>
                            <p class="font-bold text-blue-700">{{ $service->name }}</p>
                            <p class="text-lg font-black mt-1 text-gray-900">${{ number_format($service->price, 0) }}</p>
                        </div>
                    </div>

                    <!-- Sección 2: Tiempo y Lugar -->
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">{{ $datetime->isoFormat('LL') }}</p>
                                <p class="text-gray-500 text-sm">{{ $datetime->format('g:i A') }} ({{ $service->duration }} min)</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">{{ $service->type === 'virtual' ? 'Telemedicina' : $address->name }}</p>
                                <p class="text-sm text-gray-500 leading-tight">{{ $service->type === 'virtual' ? 'Se generará link de videollamada' : $address->address }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Datos Personales -->
                <div class="border-t border-gray-100 pt-6">
                    <div class="bg-blue-50 p-4 rounded-2xl flex flex-col md:flex-row justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-blue-600 uppercase">Identificación</p>
                            <p class="text-sm font-medium text-gray-800">{{ $identification }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-blue-600 uppercase">Teléfono</p>
                            <p class="text-sm font-medium text-gray-800">{{ $phone }}</p>
                        </div>
                        <div class="md:w-1/2">
                            <p class="text-[10px] font-bold text-blue-600 uppercase">Motivo</p>
                            <p class="text-sm font-medium text-gray-800 italic">"{{ $notes }}"</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl shadow-xl transition-all transform hover:-translate-y-1">
                        CONFIRMAR Y AGENDAR CITA
                    </button>
                    <a href="javascript:history.back()" class="text-center text-sm text-gray-500 font-bold hover:text-gray-700">
                        ← Corregir datos
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
