<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug', 
        'icon'
    ];

    public function professional():belongsToMany{
        return $this->belongsToMany(Professional::class);
    }

    public function serviceRequest():HasMany{
        return $this->hasMany(ServiceRequest::class);
    }
}
