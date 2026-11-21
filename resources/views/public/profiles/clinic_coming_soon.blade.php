<x-guest-layout>
    <div class="max-w-2xl mx-auto px-4 py-12 mt-5">
        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 text-center">
            <div class="w-24 h-24 mx-auto bg-slate-50 border border-slate-100 rounded-[1.5rem] flex items-center justify-center shadow-inner overflow-hidden ring-4 ring-slate-50">
                @if(!empty($clinic->user->profile_photo_path))
                    <img src="{{ asset('storage/' . $clinic->user->profile_photo_path) }}" alt="{{ $clinic->name }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                @endif
            </div>

            <h1 class="mt-5 text-2xl font-black text-slate-900 tracking-tight">{{ $clinic->name }}</h1>

            <span class="inline-flex items-center gap-1.5 mt-3 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold border border-indigo-100">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                Muy pronto
            </span>

            <p class="mt-6 text-slate-600 leading-relaxed">
                Esta clínica está integrando a su equipo médico a OpenDoctor.
                En breve tendremos a sus especialistas habilitados para que puedas agendar tu cita con ellos.
            </p>

            @if(!empty($clinic->bio))
                <p class="mt-4 text-sm text-slate-500 leading-relaxed">{{ \Illuminate\Support\Str::limit($clinic->bio, 280) }}</p>
            @endif

            <div class="mt-8">
                <a href="{{ url('/search') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition">
                    Buscar otros especialistas
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
