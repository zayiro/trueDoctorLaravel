<x-guest-layout>
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5">
        <!-- Barra de Filtros -->
        <form action="{{ route('search') }}" method="GET" class="bg-white mt-8 p-4 rounded-xl shadow-md flex flex-wrap gap-4 mb-8">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-400 uppercase">Especialidad</label>
                <select name="specialty" class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4">
                    <option value="">Cúal especialidad buscas?</option>
                    @foreach($specialties as $s)
                        <option value="{{ $s->slug }}" {{ request('specialty') == $s->slug ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px] border-l pl-4">
                <label class="block text-[10px] font-black text-slate-400 uppercase ml-3 mb-1">¿Dónde?</label>
                <select name="city" class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4">
                    <option value="">Todas las ciudades</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->slug }}" {{ request('city') == $city->slug ? 'selected' : '' }}>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>           

            <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 transition">
                Buscar
            </button>
        </form>

        <!-- Listado de Resultados -->
        <div class="bg-slate-50 min-h-screen py-12">
            <div class="max-w-5xl mx-auto px-6">
                
                <h2 class="text-2xl font-black text-slate-800 mb-8">                    
                    @if($doctors->total() === 1)
                        {{ '1 especialista encontrado' }}
                     @elseif($doctors->total() > 1)
                        {{ $doctors->total() . ' especialistas encontrados' }}
                     @endif
                </h2>

                <div class="space-y-6">
                    @forelse($doctors as $doctor)
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col md:flex-row gap-6 hover:shadow-md transition">
                            
                            <!-- Foto y Perfil -->
                            <div class="flex-shrink-0 text-center">
                                @if ($doctor->user->profile_photo_path)
                                <img src="https://ui-avatars.com{{ urlencode($doctor->user->name) }}&background=E0F2FE&color=0369A1" 
                                    class="w-24 h-24 rounded-2xl mx-auto mb-3 object-cover">
                                <span class="text-[10px] font-black uppercase px-2 py-1 bg-blue-50 text-blue-600 rounded-lg">
                                    {{ $doctor->plan }}
                                </span>
                                @else
                                   <div class="relative w-24 h-24 flex items-end justify-center overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
                                        <svg class="w-24 h-24 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://w3.org">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Información -->
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-slate-900 mb-1">{{ $doctor->user->name }}</h3>                                
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach($doctor->specialties as $spec)
                                        <span class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-lg uppercase tracking-wide border border-blue-100">
                                            {{ $spec->name }}
                                        </span>
                                    @endforeach
                                </div>

                                <div class="flex items-center space-x-1">
                                    @php $rating = round($doctor->rating ?? 0); @endphp
                                    
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $rating ? 'text-yellow-400' : 'text-gray-300' }}" 
                                            fill="currentColor" viewBox="0 0 20 20" xmlns="http://w3.org">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    @endfor

                                    <!-- Texto opcional del promedio -->
                                    <span class="ml-2 text-sm font-medium text-gray-600">
                                        ({{ $doctor->reviews_count ?? 0 }})
                                    </span>
                                </div>
                                
                                <div class="space-y-3">
                                    <div class="mt-3 font-bold">Consultorios</div>
                                    @foreach($doctor->addresses as $address)
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <svg class="w-4 h-4 mt-0.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                            <span>{{ $address->address }} {{ $address->address == 'Plataforma Online' ? '' : ',' . $address->city->name }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="space-y-3">
                                    <div class="mt-3 font-bold">Servicios</div>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @if($address->services)
                                            @foreach($address->services->take(3) as $service)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $service->name }}
                                                </span>
                                            @endforeach
                                            @if($address->services->count() > 3)
                                                <span class="text-xs text-gray-400 pt-0.5">+{{ $address->services->count() - 3 }} más</span>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400 italic">No tiene servicios registrados</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Acciones -->
                            <div class="flex flex-col justify-center gap-3 min-w-[180px]">
                                <a href="{{ route('doctor.public.profile', $doctor) }}" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Ver disponibilidad
                                </a>
                                <p class="text-[10px] text-center text-slate-400 font-medium">Citas a través de TrueDoctor</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-slate-300">
                            <p class="text-slate-500 font-bold">No encontramos especialistas con esos criterios.</p>
                            <a href="{{ route('home') }}" class="text-blue-600 underline text-sm mt-2 block">Intentar otra búsqueda</a>
                        </div>
                    @endforelse
                </div>

                <!-- Paginación -->
                <div class="mt-10">
                    {{ $doctors->links() }}
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>