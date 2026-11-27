{{-- Aviso amable cuando quien reserva tiene una cuenta de personal (doctor, clínica o administración). Variable: $staffBlock (title, paragraphs, hint, show_actions). --}}
<style>
    .sb-box { border: 1px solid #fde68a; background: #fffbeb; border-radius: 20px; padding: 24px; }
    .sb-title { font-size: 1.05rem; font-weight: 900; color: #92400e; margin: 0 0 10px; }
    .sb-p { font-size: .9rem; line-height: 1.55; color: #78350f; margin: 0 0 10px; font-weight: 500; }
    .sb-hint { font-size: .85rem; background: #fff; border: 1px dashed #fcd34d; border-radius: 12px; padding: 10px 14px; color: #92400e; margin: 12px 0 16px; }
    .sb-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .sb-btn { display: inline-block; border: 0; cursor: pointer; border-radius: 12px; padding: 10px 18px; font-size: .85rem; font-weight: 800; text-decoration: none; background: #4f46e5; color: #fff; }
    .sb-link { font-size: .85rem; font-weight: 700; color: #92400e; text-decoration: underline; }
</style>

<div class="sb-box" role="status">
    <h3 class="sb-title">{{ $staffBlock['title'] }}</h3>

    @foreach ($staffBlock['paragraphs'] as $sbParagraph)
        <p class="sb-p">{{ $sbParagraph }}</p>
    @endforeach

    @if (!empty($staffBlock['hint']))
        <div class="sb-hint">{{ $staffBlock['hint'] }}</div>
    @endif

    <div class="sb-actions">
        @if (\Illuminate\Support\Facades\Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sb-btn">Cerrar sesión y continuar como paciente</button>
            </form>
        @endif

        @if (\Illuminate\Support\Facades\Route::has('dashboard'))
            <a href="{{ route('dashboard') }}" class="sb-link">Volver a mi panel</a>
        @else
            <a href="{{ url('/') }}" class="sb-link">Volver al inicio</a>
        @endif
    </div>
</div>
