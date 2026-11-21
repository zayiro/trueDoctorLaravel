{{-- Parcial compartido de páginas de error amables. Variables: $title, $message, $icon (search|lock|clock|alert) --}}
@php
    $icons = [
        'search' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
        'lock'   => 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
        'clock'  => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'alert'  => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    ];
    $path = $icons[$icon ?? 'search'] ?? $icons['search'];
@endphp
<x-guest-layout>
    <div class="max-w-2xl mx-auto px-4 py-12 mt-5">
        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 text-center">
            <div class="w-20 h-20 mx-auto bg-indigo-50 border border-indigo-100 rounded-[1.5rem] flex items-center justify-center">
                <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                </svg>
            </div>

            <h1 class="mt-5 text-2xl font-black text-slate-900 tracking-tight">{{ $title }}</h1>
            <p class="mt-4 text-slate-600 leading-relaxed">{{ $message }}</p>

            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ url('/') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition">
                    Ir al inicio
                </a>
                <a href="{{ url('/search') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-white text-slate-700 text-sm font-bold border border-slate-200 hover:bg-slate-50 transition">
                    Buscar especialistas
                </a>
                <button type="button" onclick="history.length > 1 ? history.back() : (window.location.href='{{ url('/') }}')"
                        class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-white text-slate-700 text-sm font-bold border border-slate-200 hover:bg-slate-50 transition">
                    Volver atrás
                </button>
            </div>
        </div>
    </div>
</x-guest-layout>
