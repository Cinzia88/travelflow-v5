<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    public function emails()
    {
        return $this->hasMany(Email::class, 'email_template_id');
    }
}
