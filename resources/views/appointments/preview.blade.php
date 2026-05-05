<x-guest-layout>
    <div class="max-w-xl mx-auto py-12 px-4">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <div class="bg-blue-600 p-6 text-center text-white">
                <h2 class="text-xl font-bold text-white/90">Resumen de tu reserva</h2>
            </div>
            
            <div class="p-8 space-y-6">
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Doctor</span>
                    <span class="font-bold text-gray-800">{{ $appointment->doctor->user->name }}</span>
                </div>
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Servicio</span>
                    <span class="font-bold text-gray-800">{{ $appointment->service->name }}</span>
                </div>
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Fecha y Hora</span>
                    <span class="font-bold text-gray-800">{{ $appointment->date }} - {{ $appointment->hour }}</span>
                </div>
                
                <div class="pt-4 text-center">
                    <p class="text-2xl font-black text-blue-600 mb-6">${{ number_format($appointment->service->price, 0) }}</p>
                    
                    <form action="{{ route('appointments.final-confirm', $appointment->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-green-500 text-white py-4 rounded-2xl font-black text-lg shadow-lg hover:bg-green-600 transition">
                            Confirmar y Finalizar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
