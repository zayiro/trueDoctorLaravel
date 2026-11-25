<?php

namespace App\Models;

use App\Services\Billing\NitCheckDigit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Datos fiscales de un usuario (doctor, clínica o paciente) para la facturación electrónica.
 * El número de documento y la dirección se guardan cifrados.
 */
class BillingProfile extends Model
{
    protected $fillable = [
        'user_id', 'document_type', 'document_number', 'check_digit', 'legal_name',
        'billing_email', 'phone', 'country_code', 'department_id', 'city_id',
        'address_line', 'tax_responsibility', 'confirmed_at',
    ];

    protected $casts = [
        'document_number' => 'encrypted',
        'address_line'    => 'encrypted',
        'confirmed_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function documentTypeLabel(): string
    {
        return config("billing.document_types.{$this->document_type}.label", (string) $this->document_type);
    }

    /** «900123456-7» para NIT; el número a secas para los demás documentos. */
    public function documentWithDv(): string
    {
        $number = (string) $this->document_number;

        return ($this->document_type === 'NIT' && $this->check_digit !== null)
            ? $number . '-' . $this->check_digit
            : $number;
    }

    public static function checkDigitFor(string $number): ?string
    {
        return NitCheckDigit::for($number);
    }
}
