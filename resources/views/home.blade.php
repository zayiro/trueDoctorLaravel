<x-guest-layout>
    <div class="relative bg-white pb-20 pt-16 lg:pt-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <div class="text-center mb-12">
                <h1 class="text-5xl lg:text-7xl font-black text-slate-900 tracking-tight mb-6">
                    Encuentra a tu <span class="text-blue-600">médico ideal</span>
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Reserva citas con los mejores especialistas de tu ciudad de forma rápida y sencilla.
                </p>
            </div>

            <!-- Buscador Flotante -->
            <div class="max-w-4xl mx-auto">
                <form wire:submit.prevent="{{ route('search') }}" action="{{ route('search') }}" method="GET" class="bg-white p-4 rounded-3xl shadow-2xl border border-slate-100 flex flex-col md:flex-row gap-4">
                    
                    <!-- Selector de Especialidad -->
                    <div class="flex-1">
                        <label for="specialty" class="block text-[10px] font-black text-slate-400 uppercase ml-3 mb-1">¿Qué buscas?</label>
                        <select name="specialty" id="specialty" required class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4">
                            <option value="">Cúal especialidad buscas?</option>
                            @foreach($specialties as $specialty)
                                <option value="{{ $specialty->slug }}">{{ $specialty->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Selector de Ciudad -->
                    <div class="flex-1 border-t md:border-t-0 md:border-l border-slate-100 md:pl-4">
                        <label for="city" class="block text-[10px] font-black text-slate-400 uppercase ml-3 mb-1">¿Dónde?</label>
                        <select name="city" id="city" class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4">
                            <option value="">Todas las ciudades</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->slug }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Botón Buscar -->
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-black px-10 py-4 rounded-2xl transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2" wire:loading.attr="disabled">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Buscar
                    </button>
                    <!-- Spinner: solo visible cuando Livewire está procesando -->
                    <div wire:loading>
                        <i class="fa fa-spinner fa-spin"></i> Buscando...
                    </div>
                </form>
            </div>
        </div>

        <!-- Decoración de fondo (Opcional) -->
        <div class="absolute top-0 right-0 -z-10 opacity-10">
            <svg class="w-[600px] h-[600px]" viewBox="0 0 200 200" xmlns="http://w3.org">
                <path fill="#2563EB" d="M45,-77.2C58.3,-69.1,69.1,-56.3,77.2,-42.2C85.3,-28.1,90.7,-12.7,89.4,2.3C88.1,17.3,80.1,31.9,70.1,44.7C60.1,57.5,48.1,68.5,34.2,74.7C20.3,80.9,4.5,82.3,-11.2,79.5C-26.9,76.7,-42.5,69.7,-55.8,59.3C-69.1,48.9,-80.1,35.1,-84.6,19.6C-89.1,4.1,-87.1,-13.1,-80.4,-28.1C-73.7,-43.1,-62.3,-55.9,-49,-64C-35.7,-72.1,-20.5,-75.5,-4.7,-67.4C11.1,-59.3,22.2,-39.7,31.7,-77.2Z" transform="translate(100 100)" />
            </svg>
        </div>
    </div>
</x-guest-layout>