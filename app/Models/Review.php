<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    protected $fillable = [
        'service_request_id', 'client_id', 'professional_id',
        'rating', 'comment',
    ];

    public function serviceRequest():BelongsTo{
        return $this->belongsTo(ServiceRequest::class);
    }

    public function client():BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function professional():BelongsTo{
        return $this->belongsTo(Professional::class);
    }
}
