<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DataBundle extends Model
{
    protected $fillable = [
        'name', 'amount', 'service_id', 'code'
    ];

    protected $hidden = ['date_created', 'date_modified', 'service_id'];

    protected $with = [];

    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';

    public function service() {
        return $this->belongsTo(Service::class);
    }

    public function dataTransactions() {
        return $this->hasMany(DataTransaction::class, 'id', 'data_bundles_id');
    }
}
