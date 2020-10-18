<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AirtimeTransaction extends Model
{
    // by default status is pending = 0, active = 1, fulfilled = 2, failed = 3 and re-try = 4
    // active transactions are transactions that are currently being processed
    // fulfilled transactions are completed transations.
    // failed transactions means payment was successful but vending failed.
    // re-try means transaction is currently being re-tried.

    protected $table = 'airtime_transactions';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';

    protected $fillable = [
        'convenience_fee', 'entry_points_id', 'transaction_id', 'network', 'status', 'phone', 'email', 'amount', 'amount_paid', 'commission', 'payment_method', 'payment_ref', 'platform', 'user_id', 'service_id'
    ];

    protected $hidden = [
        'id',
        'transaction_id',
        'amount_paid',
        'commission',
        'status',
        'entry_points_id',
        'email',
        'payment_method',
        'payment_ref',
        'platform',
        'date_created',
        'date_modified',
        'user_id',
        'service_id',
        'service'
    ];

    protected $with = [
        'service'
    ];

    public function service() {
        return $this->belongsTo(Service::class);
    }
}
