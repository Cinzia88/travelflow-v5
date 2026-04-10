<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    public function quote_requests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function preventives(): HasMany
    {
        return $this->hasMany(Preventive::class);
    }
    public function emails(): HasMany 
    {
        return $this->hasMany(Email::class);
    }
}
