<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $hidden = [
        'capital',
        'created_at',
        'updated_at'
    ];
}
