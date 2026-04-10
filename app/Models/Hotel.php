<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $casts = [
        'foto' => 'array',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }


    public function hotel_preventives()
    {
        return $this->hasMany(HotelPreventive::class);
    }

}
