<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\QuoteRequestStatus;

class QuoteRequest extends Model
{
     protected $casts = [
        'stato_richiesta' => QuoteRequestStatus::class,
    ];

     protected static function booted()
    {
        // Imposta created_by SOLO durante la creazione
        static::creating(function ($req) {
            if (empty($req->created_by)) {
                $req->created_by = auth()->id();
            }
        });

        // IMPORTANTE: Previeni modifiche a created_by durante l'update
        static::updating(function ($req) {
            // Se created_by sta per essere cambiato, ripristina il valore originale
            if ($req->isDirty('created_by') && $req->getOriginal('created_by')) {
                $req->created_by = $req->getOriginal('created_by');
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }



    public function emails()
    {
        return $this->hasMany(Email::class);
    }
  

   


    public function preventives()
    {
        return $this->hasMany(Preventive::class);
    }

   
}
