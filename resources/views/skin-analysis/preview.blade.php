<x-guest-layout
    :meta-title-medical-analysis="$meta_title_medicalAnalysis" 
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
    <div class="bg-gradient-to-b from-white via-purple-50 to-slate-50">
        <div class="mx-auto max-w-5xl px-4 py-12 md:py-16">

            {{-- Encabezado --}}
            <div class="text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-purple-100 px-4 py-1.5 text-xs font-semibold text-purple-700">
                    Paso final · Resumen de tu solicitud
                </span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">Revisa tu solicitud</h1>
                <p class="mt-2 text-sm text-slate-600">Confirma los datos y el valor antes de continuar al pago.</p>
            </div>

            @if (session('error'))
                <div class="mt-6 rounded-xl border-l-4 border-red-500 bg-red-50 p-4">
                    <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Resumen de datos --}}
            <div class="mt-8 space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-md md:p-8">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Tus datos</h2>

                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate-500">📸 Fotografías</dt>
                        <dd class="font-semibold text-slate-800">{{ $sessionData['file_count'] }} imagen validada</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate-500">📧 Correo</dt>
                        <dd class="break-all text-right font-semibold text-slate-800">{{ $sessionData['email'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate-500">🌐 Idioma del reporte</dt>
                        <dd class="font-semibold text-slate-800">{{ $sessionData['language'] === 'en' ? 'English' : 'Español' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate-500">📍 Ubicación</dt>
                        <dd class="text-right font-semibold text-slate-800">{{ $sessionData['body_location'] ?: 'No indicada' }}</dd>
                    </div>
                    <div class="py-3">
                        <dt class="text-slate-500">📝 Descripción</dt>
                        <dd class="mt-1 font-medium text-slate-800">{{ $sessionData['description'] ?: 'Sin descripción' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Desglose de precio + envío --}}
            <form action="{{ route('skin-analysis.process-documents') }}" method="POST"
                class="mt-6 space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-md md:p-8">
                @csrf
                <input type="hidden" name="session_id" value="{{ $sessionId }}">

                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Valor del análisis</h2>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between border-t border-slate-200 pt-3 text-base">
                        <span class="font-bold text-slate-900">Análisis de piel con IA</span>
                        <span class="font-bold text-slate-900">${{ number_format($total, 0, ',', '.') }} COP</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3 pt-2 sm:flex-row">
                    <a href="{{ route('skin-analysis.upload') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-200">
                        ← Volver
                    </a>
                    <button type="submit"
                            class="inline-flex flex-1 items-center justify-center rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Continuar al pago
                    </button>
                </div>

                <p class="text-center text-xs leading-relaxed text-slate-500">
                    Este análisis es orientativo, no constituye un diagnóstico y no reemplaza la valoración de un profesional de la salud.
                </p>
            </form>
        </div>
    </div>
</x-guest-layout>