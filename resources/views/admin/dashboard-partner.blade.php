@php
$validation_status = auth()->user()->doctor->validation_status;

@endphp
<div>
    <!-- Alerta de éxito si se procesa el formulario -->
    @if (session('success'))
        <div class="max-w-2xl mx-auto p-4 mb-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 mt-5">
            {{ session('success') }}
        </div>
    @endif
    
    @if ($validation_status != 'approved')
        <x-verify-docs :status="$validation_status" />
    @else
        <div><a href="{{ route('partner.public.profile', auth()->user()->doctor) }}" target="_blank" title="{{ auth()->user()->name }}" class="text-blue-600 underline">Ver mi perfil publico</a></div>
    @endif
</div> 
