@php
$validation_status = auth()->user()->doctor->validation_status;

@endphp
<div>
    <!-- Alerta de éxito si se procesa el formulario -->
    @if (session('success'))
        <div class="max-w-2xl mx-auto p-4 mb-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    
    @if ($validation_status != 'approved')
        <x-verify-docs :status="$validation_status" />
    @else
        <div>Tus datos ya estan validados /var/www/html/resources/views/admin/dashboard-partner.blade.php</div>
    @endif
</div> 
