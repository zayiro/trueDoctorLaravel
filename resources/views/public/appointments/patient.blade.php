@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-12 px-4">
    <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
        <div class="p-8">
            <h2 class="text-2xl font-black text-gray-800 mb-6 text-center">Datos del Paciente</h2>

            <form action="{{ route('appointments.process_patient') }}" method="POST" class="space-y-5">
                @csrf
                <!-- Datos de la cita que vienen de los pasos anteriores -->
                <input type="hidden" name="service_id" value="{{ request('service') }}">
                <input type="hidden" name="address_id" value="{{ request('address') }}">
                <input type="hidden" name="datetime" value="{{ request('datetime') }}">

                @guest
                    <div class="bg-blue-50 p-4 rounded-2xl mb-6 text-sm text-blue-800">
                        Parece que no tienes cuenta. La crearemos automáticamente para que gestiones tu cita.
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Nombre Completo</label>
                            <input type="text" name="name" class="w-full rounded-xl border-gray-200" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Correo Electrónico</label>
                            <input type="email" name="email" class="w-full rounded-xl border-gray-200" required>
                        </div>
                    </div>
                @endguest

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Identificación -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Identificación (Cédula)</label>
                        <input type="text" 
                            name="identification" 
                            pattern="[0-9]{7,12}" 
                            title="Solo números (7 a 12 dígitos)"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            value="{{ auth()->user()?->patient?->identification }}" 
                            class="w-full rounded-xl border-gray-200 @error('identification') border-red-500 @enderror" 
                            placeholder="Ej: 1067123456" required>
                        @error('identification')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <!-- Teléfono -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Teléfono Celular</label>
                        <input type="tel" 
                            name="phone" 
                            maxlength="10"
                            pattern="[0-9]{10}"
                            title="Debe tener 10 dígitos numéricos"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            value="{{ auth()->user()?->patient?->phone }}" 
                            class="w-full rounded-xl border-gray-200 @error('phone') border-red-500 @enderror" 
                            placeholder="Ej: 3001234567" required>
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700">Motivo de la consulta</label>
                    <textarea name="notes" rows="3" class="w-full rounded-xl border-gray-200" placeholder="¿Qué síntomas tienes?" required></textarea>
                    <div>Describe brevemente el motivo por el cual estas reservando la cita.</div>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-blue-700 transition">
                    CONTINUAR AL RESUMEN
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
