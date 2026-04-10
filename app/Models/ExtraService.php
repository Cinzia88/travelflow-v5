<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraService extends Model
{
     protected $casts = [
        'allegati' => 'array',
    ];
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function preventive_items()
    {
        return $this->hasMany(PreventiveExtraService::class);
    }

    public function category()
    {
        return $this->belongsTo(CategoryExtraService::class, 'category_extra_service_id');
    }
}
