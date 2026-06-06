<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'items',
        'subtotal',
        'service_fee',
        'total',
        'payment_method',
        'payment_status',
        'status',
        'mpesa_phone',
        'mpesa_code',
        'delivery_method',
        'delivery_address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'subtotal' => 'integer',
            'service_fee' => 'integer',
            'total' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
