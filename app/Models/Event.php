<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}
