{{-- Home: promo del Chat de Salud con IA. Enlaza a la página de ventas (ai-chat.landing), que ya maneja el estado "Próximamente"/compra. --}}
<style>
    .hc-chat{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1rem;box-shadow:0 10px 35px rgba(0,0,0,.04);max-width:26rem;margin-inline:auto}
    .hc-chat-head{display:flex;align-items:center;gap:.5rem;padding-bottom:.75rem;margin-bottom:.75rem;border-bottom:1px solid #f1f5f9;font-size:.8rem;font-weight:700;color:#334155}
    .hc-dot{width:.5rem;height:.5rem;border-radius:9999px;background:#10b981}
    .hc-msg{max-width:88%;padding:.55rem .8rem;border-radius:1rem;font-size:.85rem;line-height:1.35;margin-bottom:.55rem}
    .hc-user{margin-left:auto;background:#2563eb;color:#fff;border-bottom-right-radius:.3rem}
    .hc-ai{background:#f1f5f9;color:#334155;border-bottom-left-radius:.3rem}
    .hc-report{border:1px dashed #93c5fd;background:#eff6ff;border-radius:.75rem;padding:.65rem .8rem;font-size:.78rem;color:#1e3a8a;line-height:1.4}
    .hc-report strong{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.15rem}
    .hc-cap{text-align:center;font-size:.7rem;color:#94a3b8;margin-top:.6rem}
</style>

<!-- Chat de Salud con IA -->
<section class="relative bg-white md:py-10 overflow-hidden">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full pointer-events-none opacity-40">
        <div class="absolute top-[-5%] left-[-5%] w-[400px] h-[400px] bg-indigo-200/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-[5%] right-[-5%] w-[450px] h-[450px] bg-blue-200/20 rounded-full blur-[110px]"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 z-10">
        <div class="bg-slate-50 backdrop-blur-xl rounded-3xl p-6 sm:p-10 md:p-14 border border-slate-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.02)]">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 items-center">

                <div class="md:col-span-7 space-y-6 text-left">
                    <span class="inline-flex items-center gap-1.5 px-3 py-2 pt-2.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 tracking-wide uppercase">
                        Nuevo · Chat de Salud con IA
                    </span>

                    <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Cuéntale a un asistente <span class="text-blue-600 block sm:inline">Médico IA</span> lo que sientes
                    </h2>

                    <p class="text-slate-600 text-base md:text-lg leading-relaxed max-w-xl">
                        Conversa a tu ritmo y sal con un informe claro: un plan de acción y la especialidad que te conviene ver. Sin filas, desde tu celular.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-2">
                        <span class="flex items-center gap-2 text-sm font-semibold text-slate-700 bg-white px-3 py-1.5 rounded-lg border border-slate-200/60 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Disponible 24/7
                        </span>
                        <span class="flex items-center gap-2 text-sm font-semibold text-slate-700 bg-white px-3 py-1.5 rounded-lg border border-slate-200/60 shadow-sm">
                            Informe a tu correo
                        </span>
                    </div>

                    <div class="pt-4 border-t border-slate-200/60">
                        <a href="{{ route('ai-chat.landing') }}" class="group w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-8 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/15 flex items-center justify-center gap-2.5 text-base transform active:scale-[0.98]">
                            Conocer el Chat de Salud IA
                            <svg class="w-4 h-4 text-blue-200 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <p class="mt-3 text-xs text-slate-500 max-w-xl">Es una orientación con inteligencia artificial: no reemplaza una consulta médica ni es un diagnóstico.</p>
                    </div>
                </div>

                <div class="md:col-span-5">
                    <div class="hc-chat" aria-hidden="true">
                        <div class="hc-chat-head"><span class="hc-dot"></span> Chat de Salud IA</div>
                        <div class="hc-msg hc-user">Llevo tres días con dolor de cabeza y algo de mareo.</div>
                        <div class="hc-msg hc-ai">Lamento que te sientas así. ¿El dolor es en toda la cabeza o en una zona? ¿Has tenido fiebre?</div>
                        <div class="hc-msg hc-user">En la frente, y no he tenido fiebre.</div>
                        <div class="hc-report"><strong>Tu informe</strong>Resumen, plan de acción y especialidad sugerida.</div>
                    </div>
                    <p class="hc-cap">Ejemplo ilustrativo</p>
                </div>

            </div>
        </div>
    </div>
</section>
