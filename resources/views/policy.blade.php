<x-guest-layout
    meta_title="Política de Privacidad y Protección de Datos | OpenDoctorOnline"
    meta_description="Conoce cómo protegemos y gestionamos tus datos personales y médicos en OpenDoctorOnline. Garantizamos total confidencialidad, seguridad digital y transparencia."
>
    <div class="pt-4 bg-gray-100">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div>
                <x-authentication-card-logo />
            </div>

            <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white shadow-md overflow-hidden sm:rounded-lg prose">
                {!! $policy !!}
            </div>
        </div>
    </div>
</x-guest-layout>
