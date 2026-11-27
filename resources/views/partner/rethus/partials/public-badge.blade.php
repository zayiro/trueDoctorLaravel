{{-- Insignia PÚBLICA «Registro verificado en ReTHUS». Variable: $doctor (Doctor o null).
     Solo se dibuja con verificación vigente. En cualquier otro estado (sin verificar, en revisión, no coincide, vencida)
     no sale NADA: ni texto ni espacio. REVISAR CON ABOGADO: el texto de la insignia está en config/rethus.php. --}}
@if (($doctor ?? null) instanceof \App\Models\Doctor && $doctor->hasValidRethus())
    <div style="margin-top:8px;">
        <span title="Registro confirmado como activo en la consulta pública de ReTHUS{{ $doctor->rethus_verified_at ? ' el ' . $doctor->rethus_verified_at->copy()->setTimezone('America/Bogota')->format('d/m/Y') : '' }}."
              style="display:inline-flex;align-items:center;gap:6px;padding:3px 11px;border-radius:999px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;font-size:12px;font-weight:700;line-height:1.4;">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd"/></svg>
            {{ config('rethus.texts.badge', 'Registro verificado en ReTHUS') }}
        </span>
    </div>
@endif
