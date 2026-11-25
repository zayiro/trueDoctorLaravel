@props(['id' => 'terms'])
@php
    $odTermsUrl   = \Illuminate\Support\Facades\Route::has('terms.show') ? route('terms.show') : url('/terms');
    $odPrivacyUrl = \Illuminate\Support\Facades\Route::has('privacy.show') ? route('privacy.show') : url('/privacy');
@endphp
<div class="od-terms" data-od-terms>
    <style>
        .od-terms-label{display:flex;align-items:flex-start;gap:.65rem;font-size:.875rem;line-height:1.45;color:#475569;cursor:pointer}
        .od-terms-label input[type=checkbox]{margin-top:.2rem;width:1.1rem;height:1.1rem;flex:none;border-radius:.25rem;border:1px solid #94a3b8;accent-color:#1F6E63;cursor:pointer}
        .od-terms-label a{color:#1d4ed8;text-decoration:underline;font-weight:600}
        .od-terms-label a:hover{color:#1e40af}
        .od-terms-err{margin:.4rem 0 0 1.75rem;font-size:.8125rem;font-weight:600;color:#b91c1c}
    </style>
    <label for="{{ $id }}" class="od-terms-label">
        <input type="checkbox" name="terms" id="{{ $id }}" value="1" required @checked(old('terms'))>
        <span>Acepto los <a href="{{ $odTermsUrl }}" target="_blank" rel="noopener">Términos de Servicio</a> y autorizo el tratamiento de mis datos personales según la <a href="{{ $odPrivacyUrl }}" target="_blank" rel="noopener">Política de Privacidad y Tratamiento de Datos</a>.</span>
    </label>
    @error('terms')
        <p class="od-terms-err" role="alert">{{ $message }}</p>
    @enderror
</div>
