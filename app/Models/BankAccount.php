<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cuenta donde OpenDoctor transfiere las liquidaciones. El número se guarda cifrado (cast "encrypted"). */
class BankAccount extends Model
{
    protected $fillable = [
        'accountable_type', 'accountable_id', 'bank_name', 'account_number', 'last4', 'account_type',
        'account_holder_name', 'account_holder_id', 'id_type', 'is_primary', 'changed_at',
        'verified_at', 'verified_by', 'verification_method',
    ];

    protected $hidden = ['account_number'];

    protected $casts = [
        'account_number' => 'encrypted',
        'is_primary'     => 'boolean',
        'changed_at'     => 'datetime',
        'verified_at'    => 'datetime',
    ];

    public function accountable()
    {
        return $this->morphTo();
    }

    public function masked(): string
    {
        return '••••' . ($this->last4 ?: '');
    }

    public function typeLabel(): string
    {
        return $this->account_type === 'checking' ? 'Corriente' : 'Ahorros';
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public const METHODS = [
        'nombre_banco'  => 'Nombre del titular visto en la app del banco',
        'certificacion' => 'Certificación bancaria',
        'microdeposito' => 'Micro-depósito confirmado',
    ];

    /** ¿Cambió algo sensible en las últimas $hours horas? */
    public function changedRecently(int $hours = 48): bool
    {
        return $this->changed_at && $this->changed_at->gt(now()->subHours($hours));
    }
}
