<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = ['entry_date', 'type', 'category', 'description', 'amount', 'recorded_by'];

    protected function casts(): array
    {
        return ['entry_date' => 'date:Y-m-d', 'amount' => 'integer'];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
