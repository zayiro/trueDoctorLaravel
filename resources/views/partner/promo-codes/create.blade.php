<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="sm:px-6 lg:px-10">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Crear código promocional</h2>
                    <a href="{{ route('partner.promo-codes.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver al listado</a>
                </div>

                <form action="{{ route('partner.promo-codes.store') }}" method="POST" class="space-y-6">
                    @csrf
                    @include('partner.promo-codes._form')

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('partner.promo-codes.index') }}" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200 text-sm font-medium">Cancelar</a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium shadow-sm">Crear código</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
