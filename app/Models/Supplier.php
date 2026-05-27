<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $casts = [
        'email'      => 'array',
        'telefono'   => 'array',
        'sito_web'   => 'array',
       // 'portale_web'=> 'array',
        'allegati'   => 'array',
    ];


    public function transports()
    {
        return $this->hasMany(Transport::class);
    }

    public function hotels()
    {
        return $this->hasMany(Hotel::class);
    }

    public function extra_services()
    {
        return $this->hasMany(ExtraService::class);
    }


   
}
