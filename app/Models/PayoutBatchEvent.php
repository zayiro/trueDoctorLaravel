<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutBatchEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['batch_id', 'payout_id', 'user_id', 'event', 'meta'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];
}
