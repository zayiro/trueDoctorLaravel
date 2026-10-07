<x-guest-layout
    :meta-title-medical-analysis="$meta_title_medicalAnalysis" 
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
    <div class="bg-gradient-to-b from-white via-purple-50 to-slate-50">
        <div class="mx-auto max-w-5xl px-4 py-12 md:py-16">

            <div class="text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-purple-100 px-4 py-1.5 text-xs font-semibold text-purple-700">
                    🔒 Pago seguro con Wompi
                </span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">Confirma tu pago</h1>
                <p class="mt-2 text-sm text-slate-600">Al pagar, procesamos tu fotografía y te enviamos el informe por correo.</p>
            </div>

            @if (session('error'))
                <div class="mt-6 rounded-xl border-l-4 border-red-500 bg-red-50 p-4">
                    <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            <div class="mt-8 space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-md md:p-8">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Resumen</h2>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-600">Correo del informe</span>
                        <span class="break-all text-right font-semibold text-slate-800">{{ $analysis->customer_email }}</span>
                    </div>                  
                    <div class="flex justify-between border-t border-slate-200 pt-3 text-base">
                        <span class="font-bold text-slate-900">Total a pagar</span>
                        <span class="font-bold text-slate-900">${{ number_format($analysis->total_amount, 0, ',', '.') }} COP</span>
                    </div>
                </div>

                {{-- Checkout web de Wompi: redirige al pago y regresa a redirect-url con ?id=... --}}
                <form action="https://checkout.wompi.co/p/" method="GET">
                    <input type="hidden" name="public-key" value="{{ $wompi_public_key }}">
                    <input type="hidden" name="currency" value="{{ $currency }}">
                    <input type="hidden" name="amount-in-cents" value="{{ $amount_in_cents }}">
                    <input type="hidden" name="reference" value="{{ $reference }}">
                    <input type="hidden" name="signature:integrity" value="{{ $signature }}">
                    <input type="hidden" name="redirect-url" value="{{ route('skin-analysis.payment-result', ['token' => $analysis->access_token]) }}">
                    <input type="hidden" name="customer-data:email" value="{{ $analysis->customer_email }}">

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3.5 font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Pagar ${{ number_format($analysis->total_amount, 0, ',', '.') }} COP
                    </button>
                </form>

                <p class="text-center text-xs leading-relaxed text-slate-500">
                    Este análisis es orientativo, no constituye un diagnóstico y no reemplaza la valoración de un profesional de la salud.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>    