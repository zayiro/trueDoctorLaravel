<x-guest-layout>
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5">
        @if(session('error'))
            <div class="flex items-center p-4 mt-5 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div class="ms-3 text-sm font-bold">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <!-- Barra de Filtros -->
        <form wire:submit.prevent="{{ route('search') }}" action="{{ route('search') }}" method="GET" class="bg-white mt-8 p-4 rounded-xl shadow-md flex flex-wrap gap-4 mb-8">
            <div class="flex-1 min-w-[200px]">
                <label for="specialty" class="block text-xs font-bold text-gray-400 uppercase">Especialidad</label>
                <select name="specialty" id="specialty" class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4">
                    <option value="">Cúal especialidad buscas?</option>
                    @foreach($specialties as $s)
                        <option value="{{ $s->slug }}" {{ request('specialty') == $s->slug ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px] border-l pl-4">
                <label for="city" class="block text-[10px] font-black text-slate-400 uppercase ml-3 mb-1">¿Dónde?</label>
                <select name="city" id="city" class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4">
                    <option value="">Todas las ciudades</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->slug }}" {{ request('city') == $city->slug ? 'selected' : '' }}>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>           

            <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 transition" wire:loading.attr="disabled">
                Buscar
            </button>
            <!-- Spinner: solo visible cuando Livewire está procesando -->
            <div wire:loading>
                <i class="fa fa-spinner fa-spin"></i> Buscando...
            </div>
        </form>

        @if((request()->missing('city') || empty(request('city'))) && !request()->filled('symptom'))
        <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md" role="alert">
            <div class="flex">
                <div class="py-1">
                    <svg class="fill-current h-6 w-6 text-teal-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg>
                </div>
                <div>
                    <p class="font-bold">¿Donde? {{ request()->filled('symptom') }}</p>
                    <p class="text-sm">Elige la ciudad en la que buscas al especialista</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Listado de Resultados -->
        <div class="min-h-screen mt-6">
            <div class="max-w-7xl mx-auto">
                
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
                                <img src="{{ asset('storage/' . $doctor->user->profile_photo_path) }}" 
                                    class="w-24 h-24 rounded-2xl mx-auto mb-3 object-cover">
                                <span class="text-[10px] font-black uppercase px-2 py-1 bg-blue-50 text-blue-600 rounded-lg">
                                    {{ $doctor->plan->name }}
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
                                <h3 class="text-xl font-bold text-slate-900 mb-1">{{ ucfirst($doctor->user->name) }}</h3>   
                                @if($doctor->specialties->count() > 0)                             
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach($doctor->specialties as $spec)
                                        <span class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-lg uppercase tracking-wide border border-blue-100">
                                            {{ $spec->name }}
                                        </span>
                                    @endforeach
                                </div>
                                @endif

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
                                
                                @if($doctor->addresses->count() > 0)
                                <div>
                                    <div class="mt-3 font-bold">Sedes</div>
                                    @foreach($doctor->addresses as $address)
                                        <div class="flex items-start gap-2 text-sm text-slate-600 mt-1">
                                            <svg class="w-4 h-4 mt-0.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                            <span>{{ $address->name }}</span>
                                            <span>{{ $address->address }} {{ $address->address == 'Plataforma Online' ? '' : ',' . $address->city->name }}</span>
                                        </div>
                                        
                                        <div class="flex flex-wrap gap-2 mt-1 mb-4">
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
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            
                            <!-- Acciones -->                            
                            <div class="flex flex-col justify-center gap-3 min-w-[180px]">
                                @if($doctor->addresses->count() > 0)
                                <a href="{{ route('partner.public.profile', $doctor) }}" 
                                class="bg-blue-500 hover:bg-blue-700 text-white text-center py-2 px-4 rounded shadow-lg">
                                    Ver disponibilidad
                                </a>
                                <p class="text-[10px] text-center text-slate-400 font-medium">Citas a través de {{ config('app.name', 'Reservaciones en línea') }}</p>
                                @else
                                <span class="px-2 py-1 bg-amber-50 text-amber-600 text-[10px] font-bold text-center rounded-lg uppercase tracking-wide border border-amber-100">
                                    Datos por configurar
                                </span>
                                @endif
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