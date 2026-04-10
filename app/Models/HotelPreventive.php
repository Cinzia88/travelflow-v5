<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelPreventive extends Model
{
     protected $table = 'hotel_preventives';

     protected $casts = [
        'file_fornitore_hotel' => 'array',
    ];
      public function preventive()
    {
        return $this->belongsTo(Preventive::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms_paganti()
    {
        return $this->hasMany(HotelPreventiveRoom::class, 'hotel_preventive_id', 'id')
            ->where('gratuita', false);
    }

    public function rooms_gratuite()
    {
        return $this->hasMany(HotelPreventiveRoom::class, 'hotel_preventive_id', 'id')
            ->where('gratuita', true);
    }
}
