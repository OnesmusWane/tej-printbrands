<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $appends = ['balance_due'];

    protected $fillable = [
        'quotation_id', 'invoice_number', 'client', 'email', 'service',
        'amount', 'subtotal', 'tax', 'vat_included', 'paid_amount',
        'status', 'due_date', 'payment_method', 'terms', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'subtotal' => 'integer',
            'tax' => 'integer',
            'vat_included' => 'boolean',
            'paid_amount' => 'integer',
            'due_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getBalanceDueAttribute(): int
    {
        return max(0, $this->amount - $this->paid_amount);
    }
}
