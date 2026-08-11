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
            'amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'vat_included' => 'boolean',
            'paid_amount' => 'decimal:2',
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

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }
}
