<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecialCommissionChange;
use App\Models\User;
use App\Services\Payments\CommissionRates;
use App\Services\Payments\SpecialCommissionService as Svc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpecialCommissionController extends Controller
{
    private function special($settings): callable
    {
        return fn (string $col) => ($settings && $settings->getAttribute($col) !== null) ? round((float) $settings->getAttribute($col), 2) : null;
    }

    public function index(Request $request)
    {
        $type = $request->query('type') === 'clinic' ? 'clinic' : 'doctor';
        $q = trim((string) $request->query('q', ''));
        $only = $request->boolean('only');
        $model = Svc::TYPES[$type]['model'];
        $table = (new $model)->getTable();

        $query = $model::query()->with(['user:id,name,email', 'settings']);
        if ($q !== '') {
            $query->whereHas('user', fn ($u) => $u->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")));
        }
        if ($only) {
            $query->whereHas('settings', fn ($s) => $s->where(fn ($w) => $w->whereNotNull('special_virtual_commission')->orWhereNotNull('special_presential_commission')));
        }
        $query->orderBy(User::select('name')->whereColumn('users.id', $table . '.user_id')->limit(1));

        $owners = $query->paginate(20)->withQueryString();

        $counts = [];
        foreach (Svc::TYPES as $t => $cfg) {
            $counts[$t] = $cfg['model']::whereHas('settings', fn ($s) => $s->where(fn ($w) => $w->whereNotNull('special_virtual_commission')->orWhereNotNull('special_presential_commission')))->count();
        }

        $generals = [];
        foreach (array_keys(Svc::TYPES) as $t) {
            $generals[$t] = ['virtual' => Svc::general($t, 'virtual'), 'presential' => Svc::general($t, 'presential')];
        }

        return view('administrator.commissions.index', [
            'type' => $type, 'q' => $q, 'only' => $only, 'owners' => $owners,
            'counts' => $counts, 'generals' => $generals, 'confirmAbove' => Svc::CONFIRM_ABOVE,
        ]);
    }

    public function update(Request $request, string $type, int $id)
    {
        abort_unless(isset(Svc::TYPES[$type]), 404);
        $owner = Svc::TYPES[$type]['model']::with(['user', 'settings'])->findOrFail($id);
        $name = $owner->user?->name ?? ('#' . $id);

        try {
            $v = Svc::parse($request->input('virtual'), 'Virtual');
            $p = Svc::parse($request->input('presential'), 'Presencial');

            $cur = $this->special($owner->settings);
            $risky = (Svc::needsConfirmation($v) && $v !== $cur('special_virtual_commission'))
                || (Svc::needsConfirmation($p) && $p !== $cur('special_presential_commission'));
            if ($risky && !$request->boolean('confirm')) {
                throw new \InvalidArgumentException('Un porcentaje de 0 % o mayor a ' . Svc::CONFIRM_ABOVE . ' % necesita que marques la casilla de confirmación.');
            }

            $changes = Svc::update($type, $id, ['virtual' => $v, 'presential' => $p], (string) $request->input('reason', ''), Auth::id(), $request->ip());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withInput(array_merge($request->only('virtual', 'presential', 'reason'), ['_row' => $type . '-' . $id]))->with('error', $e->getMessage());
        }

        if (!$changes) {
            return back()->with('success', "No hubo cambios para {$name}: los porcentajes ya eran esos.");
        }
        $txt = collect($changes)->map(fn ($c) => Svc::MODALITY_LABELS[$c['modality']] . ': ' . ($c['old'] === null ? 'tarifa general' : Svc::percent($c['old'])) . ' → ' . ($c['new'] === null ? 'tarifa general' : Svc::percent($c['new'])))->implode('; ');

        return back()->with('success', "Actualizado {$name}. {$txt}. Se le avisó por correo y aplica a los pagos nuevos.");
    }

    public function history(Request $request)
    {
        $type = in_array($request->query('type'), ['doctor', 'clinic'], true) ? $request->query('type') : null;
        $id = $request->filled('id') ? (int) $request->query('id') : null;

        $query = SpecialCommissionChange::query()->orderByDesc('id');
        if ($type) { $query->where('owner_type', $type); }
        if ($type && $id) { $query->where('owner_id', $id); }

        $changes = $query->paginate(30)->withQueryString();

        $names = [];
        foreach (Svc::TYPES as $t => $cfg) {
            $ids = $changes->getCollection()->where('owner_type', $t)->pluck('owner_id')->unique()->all();
            $names[$t] = $ids ? $cfg['model']::with('user:id,name')->whereIn('id', $ids)->get()->mapWithKeys(fn ($o) => [$o->id => $o->user?->name ?? ('#' . $o->id)])->all() : [];
        }
        $admins = User::whereIn('id', $changes->getCollection()->pluck('changed_by')->filter()->unique()->all())->pluck('name', 'id');

        return view('administrator.commissions.history', compact('changes', 'names', 'admins', 'type', 'id'));
    }
}
