<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div>
            <h2 class="text-2xl font-black text-gray-800 mb-6">Registro de Doctores</h2>
            
            <form action="{{ route('doctor.register.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                    <input type="text" name="name" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 p-3 border" required>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" name="email" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 p-3 border" required>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Especialidad Principal</label>
                    <select name="specialty_id" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 p-3 border">
                        @foreach ($specialties as $specialty)
                            <option value="{{$specialty->id}}">{{$specialty->name}}</option>                        
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                        <input type="password" name="password" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 p-3 border" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirmar</label>
                        <input type="password" name="password_confirmation" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 p-3 border" required>
                    </div>
                </div>

                <button type="submit" class="mt-4 w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition shadow-md">
                    Crear Cuenta de Doctor
                </button>
            </form>
        </div>
    </x-authentication-card>
</x-guest-layout>
