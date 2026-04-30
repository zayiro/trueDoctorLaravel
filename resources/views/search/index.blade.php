<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Barra de Filtros -->
    <form action="{{ route('search') }}" method="GET" class="bg-white p-4 rounded-xl shadow-md flex flex-wrap gap-4 mb-8">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-gray-400 uppercase">Especialidad</label>
            <select name="specialty" class="w-full border-none focus:ring-0 text-lg">
                <option value="">Todas las especialidades</option>
                @foreach($specialties as $s)
                    <option value="{{ $s->id }}" {{ request('specialty') == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-[200px] border-l pl-4">
            <label class="block text-xs font-bold text-gray-400 uppercase">Ciudad o Dirección</label>
            <input type="text" name="city" value="{{ request('city') }}" placeholder="Ej: Madrid" 
                   class="w-full border-none focus:ring-0 text-lg">
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 transition">
            Buscar
        </button>
    </form>

    <!-- Listado de Resultados -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            @forelse($doctors as $doctor)
                <div class="bg-white p-6 rounded-2xl shadow-sm border hover:shadow-md transition flex gap-6">
                    <!-- Foto y Perfil -->
                    <div class="w-32 h-32 bg-gray-200 rounded-xl overflow-hidden flex-shrink-0">
                        <img src="{{ $doctor->user->profile_photo_url }}" alt="Doctor" class="w-full h-full object-cover">
                    </div>

                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900">Dr. {{ $doctor->user->name }}</h3>
                        <p class="text-indigo-600 font-medium mb-2">{{ $doctor->specialty->name }}</p>
                        
                        <div class="space-y-2">
                            @foreach($doctor->addresses as $address)
                                <div class="flex items-start gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 mt-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">...</svg>
                                    <span>{{ $address->name }} - {{ $address->address }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex gap-3">
                            <a href="{{ route('doctor.profile', $doctor->id) }}" 
                               class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold">Ver Perfil</a>
                            <a href="{{ route('appointments.book', $doctor->id) }}" 
                               class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold">Reservar Cita</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-white rounded-2xl">
                    <p class="text-gray-500">No encontramos doctores con esos criterios.</p>
                </div>
            @endforelse

            {{ $doctors->links() }}
        </div>

        <!-- Sidebar (Opcional: Mapa o Filtros extra) -->
        <div class="hidden lg:block bg-gray-100 rounded-2xl h-[600px] sticky top-8 flex items-center justify-center">
            <span class="text-gray-400 font-medium italic">Mapa de ubicaciones</span>
        </div>
    </div>
</div>
