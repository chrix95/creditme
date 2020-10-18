<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'transaction_amount', 'current_balance', 'new_balance', 'transaction_type', 'transaction_description', 'status', 'transaction_reference'
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
