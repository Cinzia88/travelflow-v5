<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function preventive_transports()
    {
        return $this->hasMany(PreventiveTransport::class);
    }
}
