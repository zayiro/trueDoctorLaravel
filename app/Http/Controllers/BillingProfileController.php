<?php

namespace App\Http\Controllers;

use App\Models\BillingProfile;
use App\Models\City;
use App\Models\Department;
use App\Services\Billing\BillingSuggestions;
use App\Services\Billing\NitCheckDigit;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Datos fiscales del propio usuario (doctor, clínica o paciente). Una sola pantalla para los tres roles.
 * Cada persona solo ve y edita el suyo; el rol lo fija el middleware de las rutas.
 */
class BillingProfileController extends Controller
{
    private const ROLES = ['doctor', 'clinic', 'patient'];

    private const ROUTES = [
        'doctor'  => ['edit' => 'partner.billing.edit',        'update' => 'partner.billing.update'],
        'clinic'  => ['edit' => 'partner.clinic.billing.edit', 'update' => 'partner.clinic.billing.update'],
        'patient' => ['edit' => 'patient.billing.edit',        'update' => 'patient.billing.update'],
    ];

    public function edit(Request $request, BillingSuggestions $suggestions)
    {
        $user = $request->user();
        $role = (string) ($user?->role);
        if (! in_array($role, self::ROLES, true)) {
            return redirect()->route('admin.dashboard');
        }

        $profile = BillingProfile::where('user_id', $user->id)->first();
        $values = null;
        $suggested = [];

        if ($profile) {
            try {
                $values = [
                    'document_type'      => (string) $profile->document_type,
                    'document_number'    => (string) $profile->document_number,
                    'legal_name'         => (string) $profile->legal_name,
                    'billing_email'      => (string) $profile->billing_email,
                    'phone'              => (string) $profile->phone,
                    'department_id'      => (string) $profile->department_id,
                    'city_id'            => (string) $profile->city_id,
                    'address_line'       => (string) $profile->address_line,
                    'tax_responsibility' => (string) $profile->tax_responsibility,
                ];
            } catch (DecryptException) {
                // Si no se puede descifrar, se trata como si no existiera y se vuelve a sugerir.
                $profile = null;
            }
        }

        if ($values === null) {
            $s = $suggestions->for($user);
            $values = $s['values'];
            $suggested = $s['suggested'];
        }

        $types = config('billing.document_types');
        $allowedTypes = [];
        foreach (config("billing.allowed_by_role.$role", []) as $key) {
            if (isset($types[$key])) {
                $allowedTypes[$key] = $types[$key]['label'];
            }
        }

        return view('billing.edit', [
            'breadcrumbs'  => [
                ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
                ['name' => 'Datos fiscales'],
            ],
            'role'         => $role,
            'profile'      => $profile,
            'values'       => $values,
            'suggested'    => $suggested,
            'allowedTypes' => $allowedTypes,
            'taxOptions'   => config('billing.tax_responsibilities'),
            'taxVisible'   => in_array($role, config('billing.tax_required_roles'), true),
            'departments'  => Department::orderBy('name')->get(['id', 'name']),
            'cities'       => City::orderBy('name')->get(['id', 'name', 'department_id'])
                                ->map(fn ($c) => [(string) $c->id, (string) $c->name, (string) $c->department_id])
                                ->values()->all(),
            'sedes'        => $suggestions->sedes($user),
            'updateRoute'  => self::ROUTES[$role]['update'],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $role = (string) ($user?->role);
        if (! in_array($role, self::ROLES, true)) {
            return redirect()->route('admin.dashboard');
        }

        $type = (string) $request->input('document_type');
        $request->merge([
            'document_number' => $this->normalizeNumber($type, (string) $request->input('document_number')),
            'phone'           => preg_replace('/[^0-9+]/', '', (string) $request->input('phone')),
        ]);

        $taxRequired = in_array($role, config('billing.tax_required_roles'), true);
        $numeric = (bool) config("billing.document_types.$type.numeric", true);

        $validated = $request->validate([
            'document_type'   => ['required', Rule::in(config("billing.allowed_by_role.$role", []))],
            'document_number' => ['required', 'string', function ($attr, $value, $fail) use ($numeric) {
                $ok = $numeric
                    ? ctype_digit($value) && strlen($value) >= 5 && strlen($value) <= 15
                    : (bool) preg_match('/^[A-Za-z0-9]{5,20}$/', $value);
                if (! $ok) {
                    $fail($numeric
                        ? 'El número de documento debe tener entre 5 y 15 dígitos, sin puntos ni letras.'
                        : 'El número de documento debe tener entre 5 y 20 letras o números, sin espacios ni guiones.');
                }
            }],
            'legal_name'         => ['required', 'string', 'min:3', 'max:255'],
            'billing_email'      => ['required', 'email', 'max:255'],
            'phone'              => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'department_id'      => ['required', Rule::exists('departments', 'id')],
            'city_id'            => ['required', Rule::exists('cities', 'id')->where('department_id', $request->input('department_id'))],
            'address_line'       => ['required', 'string', 'min:5', 'max:255'],
            'tax_responsibility' => [$taxRequired ? 'required' : 'nullable', Rule::in(array_keys(config('billing.tax_responsibilities')))],
            'declaration'        => ['accepted'],
        ], [
            'document_type.in'          => 'Ese tipo de documento no está disponible para tu cuenta.',
            'phone.regex'               => 'El teléfono debe tener entre 7 y 15 dígitos (puede empezar con +).',
            'city_id.exists'            => 'La ciudad no corresponde al departamento elegido.',
            'department_id.required'    => 'Selecciona el departamento.',
            'city_id.required'          => 'Selecciona la ciudad.',
            'tax_responsibility.required' => 'Indica tu responsabilidad fiscal. Si no estás seguro, míralo en tu RUT o consúltalo con tu contador.',
            'declaration.accepted'      => 'Debes confirmar que los datos son correctos.',
        ], [
            'document_type'   => 'tipo de documento',
            'document_number' => 'número de documento',
            'legal_name'      => 'nombre o razón social',
            'billing_email'   => 'correo de facturación',
            'address_line'    => 'dirección',
        ]);

        try {
            BillingProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'document_type'      => $validated['document_type'],
                    'document_number'    => $validated['document_number'],
                    'check_digit'        => $validated['document_type'] === 'NIT' ? NitCheckDigit::for($validated['document_number']) : null,
                    'legal_name'         => trim($validated['legal_name']),
                    'billing_email'      => strtolower(trim($validated['billing_email'])),
                    'phone'              => ($validated['phone'] ?? '') !== '' ? $validated['phone'] : null,
                    'country_code'       => config('billing.country', 'CO'),
                    'department_id'      => $validated['department_id'],
                    'city_id'            => $validated['city_id'],
                    'address_line'       => trim($validated['address_line']),
                    'tax_responsibility' => $taxRequired ? ($validated['tax_responsibility'] ?? null) : null,
                    'confirmed_at'       => now(),
                ]
            );
        } catch (Throwable $e) {
            Log::error('billing_profile_save_failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'No pudimos guardar tus datos fiscales en este momento. Intenta de nuevo en unos minutos.');
        }

        Log::info('billing_profile_confirmed', ['user_id' => $user->id, 'role' => $role]);

        return redirect()->route(self::ROUTES[$role]['edit'])
            ->with('success', 'Tus datos fiscales quedaron guardados y confirmados.');
    }

    /** Quita puntos, espacios y guiones; en NIT ignora el «-DV» (el DV se calcula siempre en el servidor). */
    private function normalizeNumber(string $type, string $raw): string
    {
        $raw = trim($raw);
        if ($type === 'NIT' && preg_match('/^([\d.\s]+)-\s*\d$/', $raw, $m)) {
            $raw = $m[1];
        }
        $raw = preg_replace('/[\s.\-]/', '', $raw) ?? '';

        return $type === 'PA' ? strtoupper($raw) : $raw;
    }
}
