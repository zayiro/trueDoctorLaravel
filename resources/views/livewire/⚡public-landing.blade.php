<div class="min-h-screen bg-gray-100">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-10">
                <h1 class="text-4xl font-bold mb-4">{{ $campaign->title }}</h1>
                <p class="text-gray-600 mb-6">Promocionado por: {{ $doctor->name }}</p>
                
                <div class="prose max-w-none">
                    {!! $campaign->content !!}
                </div>

                <div class="mt-8">
                    {{-- Aquí podrías poner un formulario de contacto o registro --}}
                    <button class="bg-indigo-600 text-white px-6 py-2 rounded">
                        ¡Me interesa!
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
