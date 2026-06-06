<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PricingTier extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Auto-generate slug on create
        static::creating(function (PricingTier $tier) {
            if (empty($tier->slug)) {
                $base = Str::slug($tier->name ?? 'tier');
                $slug = $base;
                $i    = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $tier->slug = $slug;
            }
        });

        // Enforce single "most popular" tier at the model level
        static::saving(function (PricingTier $tier) {
            if ($tier->isDirty('is_popular') && $tier->is_popular) {
                static::where('id', '!=', $tier->id)->update(['is_popular' => false]);
            }
        });
    }

    protected $fillable = ['slug', 'name', 'price', 'old_price', 'description', 'features', 'color', 'is_popular', 'is_visible', 'orders_count', 'sort_order'];

    protected $appends = ['highlighted', 'desc'];

    protected function casts(): array
    {
        return ['features' => 'array', 'is_popular' => 'boolean', 'is_visible' => 'boolean', 'orders_count' => 'integer', 'sort_order' => 'integer'];
    }

    public function getHighlightedAttribute(): bool
    {
        return $this->is_popular;
    }

    public function getDescAttribute(): ?string
    {
        return $this->description;
    }
}
