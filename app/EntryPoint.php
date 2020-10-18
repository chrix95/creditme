<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EntryPoint extends Model
{
    //
    protected $fillable = [
        'phone_number', 
        'total_amount', 
        'reference', 
        'payment_reference', 
        'payment_method', 
        'platform', 
        'transaction_count', 
        'status'
    ];

    protected $hidden = [
        'id',
        'payment_reference',
        'payment_method',
        'platform',
        'updated_at'
    ];

    public function airtimeTransaction () {
        return $this->hasMany(AirtimeTransaction::class, 'entry_points_id', 'reference');
    }

    public function dataTransaction () {
        return $this->hasMany(DataTransaction::class, 'entry_points_id', 'reference');
    }

}
