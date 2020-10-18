<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FailedEmail extends Model
{
    protected $fillable = [
        'transaction_type', 'transaction_id', 'trials'
    ];

    // transaction_type: power, airtime, tv, data
}
