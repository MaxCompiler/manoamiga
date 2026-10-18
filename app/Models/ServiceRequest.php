<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = [
        'client_id', 'professional_id', 'category_id',
        'description', 'address', 'latitude', 'longitude',
        'status', 'price', 'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datatime'
    ];

    public function client():BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function professional():BelongsTo{
        return $this->belongsTo(Professional::class);
    }

    public function categories():BelongsTo{
        return $this->belongsTo(Category::class);
    }

    public function review():HasOne{
        return $this->hasOne(Review::class);
    }
}
