<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = ['entry_date', 'category', 'description', 'income', 'expense', 'recorded_by'];

    protected $appends = ['net'];

    protected function casts(): array
    {
        return ['entry_date' => 'date:Y-m-d', 'income' => 'integer', 'expense' => 'integer'];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getNetAttribute(): int
    {
        return (int) ($this->income ?? 0) - (int) ($this->expense ?? 0);
    }
}
