<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Soporte de liquidación (PDF) de un beneficiario dentro de un lote. */
class PayoutReport extends Model
{
    protected $fillable = [
        'batch_id', 'payable_type', 'payable_id', 'number', 'transfer_reference', 'total_amount', 'lines_count',
        'paid_at', 'pdf_path', 'pdf_hash', 'generated_at', 'emailed_at', 'email_to',
    ];

    protected $casts = [
        'paid_at'      => 'datetime',
        'generated_at' => 'datetime',
        'emailed_at'   => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(PayoutBatch::class, 'batch_id');
    }

    public function payable()
    {
        return $this->morphTo();
    }
}
