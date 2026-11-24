<x-guest-layout>
    <div class="max-w-xl mx-auto py-16 px-4 mt-6">
        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 text-center space-y-4">
            <div class="mx-auto w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">💬</div>
            <h1 class="text-2xl font-black text-gray-800">{{ $title }}</h1>
            <p class="text-sm text-slate-600 leading-relaxed">{{ $text }}</p>

            @if (session('error'))
                <p class="text-sm font-semibold text-amber-700">{{ session('error') }}</p>
            @endif

            <a href="{{ $ctaUrl }}"
               class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700">
                {{ $ctaLabel }}
            </a>
        </div>
    </div>
</x-guest-layout>
