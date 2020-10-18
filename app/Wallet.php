<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'balance'
    ];

    protected $hidden = [
        'id', 'user_id', 'created_at', 'updated_at'
    ];

    protected $with = [
        'transactions'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
