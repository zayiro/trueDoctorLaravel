<?php

namespace App\Services\Payments;

use App\Mail\BankAccountChangedMail;
use App\Models\BankAccount;
use App\Models\BankAccountEvent;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BankAccountService
{
    public const BANKS = [
        'Bancolombia', 'Davivienda', 'Banco de Bogotá', 'BBVA Colombia', 'Banco de Occidente', 'Banco Popular', 'Banco AV Villas',
        'Scotiabank Colpatria', 'Itaú', 'Banco Caja Social', 'Banco Agrario', 'Bancoomeva', 'Banco Falabella', 'Banco Pichincha',
        'Banco Mundo Mujer', 'Banco W', 'Banco Finandina', 'Banco GNB Sudameris', 'Bancamía', 'Nequi', 'Daviplata', 'Lulo Bank',
        'Nu Colombia', 'RappiPay',
    ];

    public static function digits(?string $v): string
    {
        return preg_replace('/\D+/', '', (string) $v);
    }

    public static function forOwner(string $class, int $id): ?BankAccount
    {
        return BankAccount::where('accountable_type', $class)->where('accountable_id', $id)->where('is_primary', true)->orderByDesc('id')->first();
    }

    /** Nombre, documento y correo del beneficiario. */
    public static function owner(string $class, int $id): array
    {
        $isClinic = $class === Clinic::class;
        $m = $isClinic ? Clinic::with('user')->find($id) : Doctor::with('user')->find($id);

        return [
            'isClinic' => $isClinic,
            'name'     => $m?->user?->name ?? ($isClinic ? 'Clínica' : 'Médico'),
            'document' => self::digits($isClinic ? $m?->nit : $m?->identification),
            'email'    => $m?->user?->email,
        ];
    }

    /** Alertas para el administrador. Solo advierten; no bloquean. @return array<string,string> */
    public static function flags(string $class, int $id): array
    {
        $acc = self::forOwner($class, $id);
        if (!$acc) {
            return ['missing' => 'Sin cuenta bancaria registrada: no se puede registrar la transferencia. Pídele que la cargue en «Mis liquidaciones».'];
        }

        $flags = [];
        if ($acc->changedRecently()) {
            $flags['recent'] = 'Cuenta cambiada hace menos de 48 horas (' . $acc->changed_at->timezone('America/Bogota')->format('d/m H:i') . '). Confirma con el beneficiario antes de transferir.';
        }

        $doc = self::owner($class, $id)['document'];
        $holder = self::digits($acc->account_holder_id);
        if ($doc !== '' && $holder !== '' && !self::sameDocument($doc, $holder)) {
            $flags['holder'] = 'El documento del titular de la cuenta no coincide con el documento registrado del beneficiario.';
        }

        if (!$acc->isVerified()) {
            $flags['unverified'] = 'Cuenta sin verificar: es obligatorio verificarla antes de registrar la transferencia. Haz la transferencia, confirma en tu banco que el nombre del titular coincide y márcala como verificada.';
        }

        return $flags;
    }

    /** Marca la cuenta como verificada y deja constancia de quién, cuándo y cómo. */
    public static function verify(BankAccount $acc, string $method, ?int $userId, ?string $ip): bool
    {
        if (!isset(BankAccount::METHODS[$method])) { return false; }

        $acc->forceFill(['verified_at' => now(), 'verified_by' => $userId, 'verification_method' => $method])->save();
        BankAccountEvent::create([
            'bank_account_id' => $acc->id, 'accountable_type' => $acc->accountable_type, 'accountable_id' => $acc->accountable_id,
            'user_id' => $userId, 'event' => 'verified', 'ip' => $ip, 'meta' => ['method' => $method],
        ]);

        return true;
    }

    /** Igual, o uno es el otro más el dígito de verificación del NIT. */
    public static function sameDocument(string $a, string $b): bool
    {
        if ($a === $b) { return true; }
        [$short, $long] = strlen($a) <= strlen($b) ? [$a, $b] : [$b, $a];

        return strlen($long) - strlen($short) === 1 && str_starts_with($long, $short);
    }

    /** @return array{0: BankAccount, 1: bool, 2: bool} [cuenta, creada, cambióAlgoSensible] */
    public static function save(string $class, int $id, array $data, ?User $actor, ?string $ip): array
    {
        [$acc, $created, $changed, $meta] = DB::transaction(function () use ($class, $id, $data, $actor, $ip) {
            $acc = self::forOwner($class, $id);
            $created = !$acc;
            $number = self::digits($data['account_number'] ?? '');
            $fromLast4 = $acc?->last4;

            $new = [
                'bank_name'           => trim($data['bank_name']),
                'account_type'        => $data['account_type'],
                'account_holder_name' => trim($data['account_holder_name']),
                'account_holder_id'   => self::digits($data['account_holder_id']),
                'id_type'             => $data['id_type'],
            ];
            if ($number !== '') { $new['account_number'] = $number; $new['last4'] = substr($number, -4); }

            $fields = [];
            if ($created) {
                $acc = BankAccount::create($new + ['accountable_type' => $class, 'accountable_id' => $id, 'is_primary' => true, 'changed_at' => now()]);
                $fields = array_keys($new);
            } else {
                foreach ($new as $k => $v) {
                    $cur = $k === 'account_number' ? (string) $acc->account_number : (string) $acc->{$k};
                    if ($cur !== (string) $v) { $fields[] = $k; }
                }
                if ($fields) {
                    $sensitive = array_intersect($fields, ['account_number', 'account_holder_id', 'account_holder_name', 'bank_name', 'account_type']);
                    $acc->fill($new);
                    if ($sensitive) { $acc->changed_at = now(); $acc->verified_at = null; $acc->verified_by = null; $acc->verification_method = null; }
                    $acc->save();
                }
            }

            $changed = $created || (bool) array_intersect($fields, ['account_number', 'account_holder_id', 'account_holder_name', 'bank_name', 'account_type']);
            $meta = ['fields' => $fields, 'from_last4' => $fromLast4, 'to_last4' => $acc->last4];
            if ($created || $fields) {
                BankAccountEvent::create([
                    'bank_account_id' => $acc->id, 'accountable_type' => $class, 'accountable_id' => $id,
                    'user_id' => $actor?->id, 'event' => $created ? 'created' : 'changed', 'ip' => $ip, 'meta' => $meta,
                ]);
            }

            return [$acc, $created, $changed, $meta];
        });

        if ($changed) { self::notify($class, $id, $acc, $created); }

        return [$acc, $created, $changed];
    }

    /** Aviso de seguridad al beneficiario y al equipo. No debe romper el guardado. */
    public static function notify(string $class, int $id, BankAccount $acc, bool $created): void
    {
        try {
            $o = self::owner($class, $id);
            $when = now('America/Bogota')->format('d/m/Y H:i');
            if ($o['email']) {
                Mail::to($o['email'])->send(new BankAccountChangedMail(false, $o['name'], $acc->masked(), $acc->bank_name, $acc->typeLabel(), $when, $created));
            }
            foreach (self::teamRecipients() as $to) {
                Mail::to($to)->send(new BankAccountChangedMail(true, $o['name'], $acc->masked(), $acc->bank_name, $acc->typeLabel(), $when, $created));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Correos del equipo que reciben los avisos de seguridad: la dirección de administración configurada
     * (MAIL_ADMIN_ADDRESS); si no existe, los correos de los usuarios admin.
     */
    private static function teamRecipients(): \Illuminate\Support\Collection
    {
        $configured = config('services.mail_site.mail_admin_address');
        if ($configured) {
            return collect([$configured]);
        }

        return User::whereIn('role', ['administrator', 'admin'])->pluck('email')->filter()->unique();
    }

    /** Registra que alguien del equipo vio el número completo. */
    public static function logView(BankAccount $acc, ?int $userId, ?string $ip, ?int $batchId = null): void
    {
        BankAccountEvent::create([
            'bank_account_id' => $acc->id, 'accountable_type' => $acc->accountable_type, 'accountable_id' => $acc->accountable_id,
            'user_id' => $userId, 'event' => 'viewed', 'ip' => $ip, 'meta' => ['batch_id' => $batchId],
        ]);
    }
}
