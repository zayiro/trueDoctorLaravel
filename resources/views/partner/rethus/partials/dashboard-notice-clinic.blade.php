{{-- Aviso en el dashboard de la CLÍNICA: cuántos de sus doctores activos aún no tienen verificación vigente en ReTHUS.
     No sale si todos la tienen o si la clínica no tiene doctores. Variable: $clinic (Clinic o null).
     REVISAR CON ABOGADO: el texto de la incapacidad. --}}
@php
    $rcTotal = 0;
    $rcPending = 0;
    if (($clinic ?? null) instanceof \App\Models\Clinic) {
        $rcDocs = $clinic->doctors->filter(fn ($d) => (bool) $d->active);
        $rcTotal = $rcDocs->count();
        $rcPending = $rcDocs->filter(fn ($d) => !$d->hasValidRethus())->count();
    }
    $rcUrl = \Illuminate\Support\Facades\Route::has('partner.clinic.doctors.index') ? route('partner.clinic.doctors.index') : null;
@endphp
@if ($rcPending > 0)
<style>
    .rtc { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin: 12px 12px 20px; padding: 14px 18px; border-radius: 18px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: 13px; font-weight: 700; line-height: 1.5; }
    .rtc a { color: #92400e; text-decoration: underline; font-weight: 900; white-space: nowrap; }
    .rtc strong { font-weight: 900; }
</style>
<div class="rtc" role="alert">
    <span>
        <strong>La incapacidad solo se podrá emitir si el doctor está «verificado en ReTHUS».</strong><br>
        {{ $rcPending }} de {{ $rcTotal }} {{ $rcTotal === 1 ? 'doctor activo de tu clínica aún no lo está' : 'doctores activos de tu clínica aún no lo están' }}.
    </span>
    @if ($rcUrl)
        <a href="{{ $rcUrl }}">Ver mis doctores →</a>
    @endif
</div>
@endif
