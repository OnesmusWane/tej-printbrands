<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceBooking extends Model
{
    use HasFactory;

    protected $fillable = ['booking_number', 'user_id', 'service_request_id', 'client', 'email', 'phone', 'service', 'preferred_date', 'preferred_time', 'delivery_date', 'duration', 'location', 'budget', 'project_description', 'description', 'notes', 'status', 'price'];

    protected function casts(): array
    {
        return ['preferred_date' => 'date', 'delivery_date' => 'date', 'preferred_time' => 'datetime:H:i', 'price' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }
}
