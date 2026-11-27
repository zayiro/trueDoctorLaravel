<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Services\Rethus\RethusVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Cola del administrador: verificar a mano en ReTHUS (la consulta pública exige captcha). */
class AdminRethusController extends Controller
{
    public const FILTERS = ['pending', 'expiring', 'expired', 'mismatch', 'verified', 'all'];

    public function index(Request $request)
    {
        $filter = in_array($request->query('f'), self::FILTERS, true) ? $request->query('f') : 'pending';
        $q = trim((string) $request->query('q', ''));
        $days = (int) config('rethus.expiring_notice_days', 30);

        $base = Doctor::query()->with(['user', 'specialties']);
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $base->where(function ($w) use ($like) {
                $w->where('identification', 'like', $like)->orWhere('medical_license', 'like', $like)
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
        }

        $counts = [
            'pending'  => Doctor::where('rethus_status', 'requested')->count(),
            'expiring' => Doctor::where('rethus_status', 'verified')->whereBetween('rethus_expires_at', [now(), now()->addDays($days)])->count(),
            'expired'  => Doctor::where(fn ($w) => $w->where('rethus_status', 'expired')->orWhere(fn ($x) => $x->where('rethus_status', 'verified')->where('rethus_expires_at', '<', now())))->count(),
            'mismatch' => Doctor::where('rethus_status', 'mismatch')->count(),
            'verified' => Doctor::where('rethus_status', 'verified')->where('rethus_expires_at', '>=', now())->count(),
        ];

        $query = clone $base;
        match ($filter) {
            'pending'  => $query->where('rethus_status', 'requested')->orderBy('rethus_requested_at'),
            'expiring' => $query->where('rethus_status', 'verified')->whereBetween('rethus_expires_at', [now(), now()->addDays($days)])->orderBy('rethus_expires_at'),
            'expired'  => $query->where(fn ($w) => $w->where('rethus_status', 'expired')->orWhere(fn ($x) => $x->where('rethus_status', 'verified')->where('rethus_expires_at', '<', now())))->orderBy('rethus_expires_at'),
            'mismatch' => $query->where('rethus_status', 'mismatch')->latest('updated_at'),
            'verified' => $query->where('rethus_status', 'verified')->where('rethus_expires_at', '>=', now())->orderBy('rethus_expires_at'),
            default    => $query->where('rethus_status', '!=', 'none')->latest('updated_at'),
        };

        return view('administrator.rethus.index', [
            'doctors' => $query->paginate(15)->withQueryString(),
            'filter'  => $filter,
            'q'       => $q,
            'counts'  => $counts,
        ]);
    }

    public function verify(Request $request, Doctor $doctor, RethusVerificationService $service)
    {
        $data = $request->validate([
            'confirmed_text' => ['required', 'string', 'min:3', 'max:255'],
            'note'           => ['nullable', 'string', 'max:1000'],
        ], [
            'confirmed_text.required' => 'Escribe qué confirmaste en ReTHUS (por ejemplo: «Médico — Activo»).',
            'confirmed_text.min'      => 'Escribe qué confirmaste en ReTHUS (mínimo 3 caracteres).',
        ]);

        $service->verify($doctor, Auth::id(), $data['confirmed_text'], $data['note'] ?? null);

        return back()->with('success', 'Registro verificado. El doctor recibió un correo y su badge ya es visible.');
    }

    public function mismatch(Request $request, Doctor $doctor, RethusVerificationService $service)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'in:' . implode(',', array_keys((array) config('rethus.mismatch_reasons', [])))],
            'note'   => ['nullable', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Elige el motivo por el que no coincide.',
            'reason.in'       => 'El motivo elegido no es válido.',
        ]);

        $service->mismatch($doctor, Auth::id(), $data['reason'], $data['note'] ?? null);

        return back()->with('success', 'Marcado como «no coincide». El doctor recibió un correo con el motivo.');
    }
}
