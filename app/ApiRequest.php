<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiRequest extends Model
{
    // status = 0 for failed transactions and 1 for successful transactions
    protected $fillable = [
        'request', 'response', 'request_timestamp', 'response_timestamp', 'api_id', 'status', 'receiver', 'ref', 'response_hash'
    ];
}
