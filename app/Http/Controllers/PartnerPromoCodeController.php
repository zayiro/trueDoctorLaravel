<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use App\Services\Promo\PromoCodeOwnerResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Códigos promocionales de doctores particulares y clínicas. El descuento lo asume quien crea el código:
 * sale de lo que recibe; la comisión de OpenDoctor no cambia. Solo aplican a citas.
 */
class PartnerPromoCodeController extends Controller
{
    public function __construct(private readonly PromoCodeOwnerResolver $owners)
    {
    }

    public function index(Request $request)
    {
        $owner = $this->owners->resolve($request->user());
        abort_if($owner['type'] === null, 403);

        $promoCodes = PromoCode::ownedBy($owner['type'], $owner['id'])->latest()->paginate(15);

        return view('partner.promo-codes.index', [
            'promoCodes' => $promoCodes,
            'owner'      => $owner,
            'maxPercent' => PromoCode::ownerMaxPercent(),
            'breadcrumbs' => $this->crumbs(),
        ]);
    }

    public function create(Request $request)
    {
        $owner = $this->creator($request);

        return view('partner.promo-codes.create', [
            'owner'       => $owner,
            'maxPercent'  => PromoCode::ownerMaxPercent(),
            'breadcrumbs' => $this->crumbs('Nuevo código'),
        ]);
    }

    public function store(Request $request)
    {
        $owner = $this->creator($request);
        $data  = $this->validated($request, null);

        PromoCode::create($data + [
            'owner_type' => $owner['type'],
            'owner_id'   => $owner['id'],
            'user_id'    => null,
            'applies_to' => [PromoCode::MODULE_APPOINTMENT],
            'uses'       => 0,
        ]);

        return redirect()->route('partner.promo-codes.index')->with('status', 'Código promocional creado.');
    }

    public function edit(Request $request, PromoCode $promo_code)
    {
        $owner = $this->creator($request);
        $this->assertOwns($owner, $promo_code);

        return view('partner.promo-codes.edit', [
            'owner'       => $owner,
            'promoCode'   => $promo_code,
            'maxPercent'  => PromoCode::ownerMaxPercent(),
            'breadcrumbs' => $this->crumbs('Editar código'),
        ]);
    }

    public function update(Request $request, PromoCode $promo_code)
    {
        $owner = $this->creator($request);
        $this->assertOwns($owner, $promo_code);

        $promo_code->update($this->validated($request, $promo_code));

        return redirect()->route('partner.promo-codes.index')->with('status', 'Código promocional actualizado.');
    }

    public function destroy(Request $request, PromoCode $promo_code)
    {
        $owner = $this->creator($request);
        $this->assertOwns($owner, $promo_code);

        if ((int) $promo_code->uses > 0) {
            return redirect()->route('partner.promo-codes.index')
                ->with('error', 'Este código ya se usó, no se puede eliminar. Desactívalo para que no se pueda usar más.');
        }

        $promo_code->delete();

        return redirect()->route('partner.promo-codes.index')->with('status', 'Código promocional eliminado.');
    }

    /** Quien crea/edita/borra: debe poder crear códigos ahora mismo. */
    private function creator(Request $request): array
    {
        $owner = $this->owners->resolve($request->user());
        abort_unless($owner['can_create'], 403, $owner['reason'] ?? 'No autorizado.');

        return $owner;
    }

    private function assertOwns(array $owner, PromoCode $promo): void
    {
        abort_unless($promo->owner_type === $owner['type'] && (int) $promo->owner_id === (int) $owner['id'], 404);
    }

    private function validated(Request $request, ?PromoCode $current): array
    {
        $max = PromoCode::ownerMaxPercent();

        $data = $request->validate([
            'code'       => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('promo_codes', 'code')->ignore($current?->id)],
            'type'       => ['required', Rule::in(['fixed', 'percent'])],
            'reward'     => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'max_uses'   => ['nullable', 'integer', 'min:1'],
            'starts_at'  => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ], [
            'code.unique'     => 'Ese código ya existe. Elige otro.',
            'code.regex'      => 'El código solo puede tener letras, números, guion y guion bajo.',
            'reward.gt'       => 'El descuento debe ser mayor que 0.',
        ]);

        if ($data['type'] === 'percent' && (float) $data['reward'] > $max) {
            back()->withInput()->withErrors(['reward' => "El descuento no puede superar el {$max} %."])->throwResponse();
        }

        $data['code']      = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');
        $data['max_uses']  = $data['max_uses'] ?? null;

        return $data;
    }

    private function crumbs(?string $last = null): array
    {
        $crumbs = [
            ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
            ['name' => 'Códigos promocionales', 'href' => $last ? route('partner.promo-codes.index') : null],
        ];

        if ($last) {
            $crumbs[] = ['name' => $last];
        }

        return $crumbs;
    }
}
