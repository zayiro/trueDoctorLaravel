<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccountEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['bank_account_id', 'accountable_type', 'accountable_id', 'user_id', 'event', 'ip', 'meta'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];
}
