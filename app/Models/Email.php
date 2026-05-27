<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
     protected $casts = [
        'email_cc' => 'array',
        'allegati' => 'array',
        'is_draft' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($email) {
            if (auth()->check()) {
                $email->sent_by = auth()->id();
            }
        });

        //per evitare errori di integrità referenziale quando si elimina un'email, stacchiamo i preventivi associati, così non vengono eliminati
        static::deleting(function ($email) {
            $email->preventives()->detach();
        });
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function template_email()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
     public function preventives()
    {
        return $this->belongsToMany(Preventive::class, 'email_preventive');
    }
    public function quote_request()
    {
        return $this->belongsTo(QuoteRequest::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
