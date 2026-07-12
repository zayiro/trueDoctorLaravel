<footer class="bg-white border-t border-slate-100 py-8 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            <!-- Lado Izquierdo: Branding / Logo -->
            <div class="flex items-center gap-2">
                <span class="text-lg font-black text-slate-900 tracking-tight">
                    Open<span class="text-indigo-600">Doctor</span><span class="text-emerald-500">Online</span>
                </span>
                <span class="text-xs font-semibold text-slate-400">| © {{ date('Y') }}</span>
            </div>

            <!-- Centro: Enlaces de navegación rápida -->
            <div class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm font-medium text-slate-500">
                <a href="{{ route('contact.show') }}" class="hover:text-indigo-600 transition-colors">Contáctenos</a>
                <a href="{{ route('about.show') }}" class="hover:text-indigo-600 transition-colors">¿Quiénes somos?</a>
                <a href="{{ route('terms.show') }}" class="hover:text-indigo-600 transition-colors">Términos de Servicio</a>
                <a href="{{ route('privacy.show') }}" class="hover:text-indigo-600 transition-colors">Política de Privacidad</a>
                <a href="{{ route('support.show') }}" class="hover:text-indigo-600 transition-colors">Soporte Técnico</a>
                <a href="{{ route('plans.index') }}" class="hover:text-indigo-600 transition-colors">Planes</a>
                <a href="https://www.websispro-gov.co" class="hover:text-indigo-600 transition-colors" target="_blank">ReTHUS</a>
            </div>

            <!-- Lado Derecho: Estado del Sistema / Redes -->
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    SaaS Activo
                </span>
            </div>

        </div>
        
        <!-- Línea inferior de copyright formal -->
        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-400">
            Todos los derechos reservados. Desarrollado para <a href="https://opendoctor.online" class="font-semibold hover:text-indigo-600 transition-colors">opendoctor.online</a>
        </div>
    </div>
</footer>
