<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = ['request_number', 'client', 'email', 'phone', 'service', 'budget', 'timeline', 'priority', 'status', 'description'];

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(ServiceBooking::class);
    }
}
