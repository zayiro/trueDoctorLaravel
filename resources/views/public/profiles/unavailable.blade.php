<x-guest-layout>
    <div class="max-w-2xl mx-auto px-4 py-12 mt-5">
        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 text-center">
            <div class="w-20 h-20 mx-auto bg-indigo-50 border border-indigo-100 rounded-[1.5rem] flex items-center justify-center">
                <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>

            <h1 class="mt-5 text-2xl font-black text-slate-900 tracking-tight">{{ $title }}</h1>

            <p class="mt-4 text-slate-600 leading-relaxed">{{ $message }}</p>

            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ url('/search') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition">
                    Buscar especialistas
                </a>
                <a href="{{ url('/') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-white text-slate-700 text-sm font-bold border border-slate-200 hover:bg-slate-50 transition">
                    Ir al inicio
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
