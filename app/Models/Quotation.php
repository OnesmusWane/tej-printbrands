<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = ['service_request_id', 'quote_request_id', 'quote_number', 'client', 'email', 'service', 'subtotal', 'tax', 'total', 'status', 'terms', 'sent_at'];

    protected function casts(): array
    {
        return ['subtotal' => 'integer', 'tax' => 'integer', 'total' => 'integer', 'sent_at' => 'datetime'];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }
}
