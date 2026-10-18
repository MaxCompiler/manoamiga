<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professional extends Model
{
    protected $fillable = [
        'user_id','bio', 'phone', 'address',
        'latitude', 'longitude', 'coverage_radius_km',
        'is_verified', 'is_available',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_available' => 'boolean'
    ];

    public function user():BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany{
        return $this->belongsToMany(Category::class);
    }

    public function serviceRequest():HasMany{
        return $this->hasMany(ServiceRequest::class);
    }

    public function review():HasMany{
        return $this->hasMany(Review::class);
    }
}
