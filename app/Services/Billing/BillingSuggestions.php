<?php

namespace App\Services\Billing;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Valores SUGERIDOS para el formulario de datos fiscales, a partir de lo que ya existe.
 * Nada de esto se guarda solo: la persona lo ve en pantalla, lo corrige y lo confirma.
 *
 * Reglas:
 *  - La dirección, la ciudad y el departamento fiscales del doctor y la clínica NO se sugieren
 *    (la sede donde atienden puede no ser la del RUT). Solo se ofrece el botón «usar mi sede».
 *  - La responsabilidad fiscal nunca se sugiere.
 */
class BillingSuggestions
{
    public const FIELDS = [
        'document_type', 'document_number', 'legal_name', 'billing_email', 'phone',
        'department_id', 'city_id', 'address_line', 'tax_responsibility',
    ];

    /**
     * @return array{values: array<string,string>, suggested: array<int,string>}
     */
    public function for(User $user): array
    {
        $values = array_fill_keys(self::FIELDS, '');
        $suggested = [];
        $set = function (string $field, ?string $value) use (&$values, &$suggested) {
            $value = trim((string) $value);
            if ($value !== '') {
                $values[$field] = $value;
                $suggested[] = $field;
            }
        };

        $set('legal_name', $user->name);
        $set('billing_email', $user->email);

        switch ($user->role) {
            case 'doctor':
                $doctor = $user->doctor;
                $set('document_type', 'CC');
                $set('document_number', $this->cleanNumber($doctor?->identification));
                $set('phone', $this->cleanPhone($doctor?->phone));
                break;

            case 'clinic':
                $clinic = $user->clinic;
                $set('document_type', 'NIT');
                [$number] = NitCheckDigit::split((string) $clinic?->nit);
                $set('document_number', $number);
                $set('phone', $this->cleanPhone($clinic?->phone));
                break;

            case 'patient':
                $patient = $user->patient;
                $set('document_type', 'CC');
                $set('document_number', $this->cleanNumber($this->safe(fn () => $patient?->identification)));
                $set('phone', $this->cleanPhone($this->safe(fn () => $patient?->phone)));
                $set('department_id', $patient?->department_id);
                $set('city_id', $patient?->city_id);
                break;
        }

        return ['values' => $values, 'suggested' => array_values(array_unique($suggested))];
    }

    /**
     * Sedes físicas activas para el botón «usar la dirección de mi consultorio».
     * Nunca la sede virtual. Las sedes borradas ya las excluye SoftDeletes.
     *
     * @return array<int, array{name:string,address:string,city_id:?string,department_id:?string}>
     */
    public function sedes(User $user): array
    {
        $query = match ($user->role) {
            'doctor' => $user->doctor?->addresses()->whereNull('clinic_id'),
            'clinic' => $user->clinic?->addresses(),
            default  => null,
        };

        if (! $query) {
            return [];
        }

        return $query->where('type', 'physical')
            ->where('status', true)
            ->with('city')
            ->orderBy('name')
            ->get()
            ->map(fn ($a) => [
                'name'          => (string) $a->name,
                'address'       => (string) $a->address,
                'city_id'       => $a->city_id ? (string) $a->city_id : null,
                'department_id' => $a->city?->department_id ? (string) $a->city->department_id : null,
            ])
            ->values()
            ->all();
    }

    private function cleanNumber(?string $raw): string
    {
        return preg_replace('/[\s.\-]/', '', trim((string) $raw)) ?? '';
    }

    private function cleanPhone(?string $raw): string
    {
        return preg_replace('/[^0-9+]/', '', (string) $raw) ?? '';
    }

    /** Lee un atributo cifrado; si no se puede descifrar, devuelve null en vez de romper la pantalla. */
    private function safe(callable $read): ?string
    {
        try {
            $value = $read();

            return $value === null ? null : (string) $value;
        } catch (DecryptException) {
            return null;
        }
    }
}
