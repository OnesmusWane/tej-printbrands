<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $base = Str::slug($product->name ?? 'product');
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $product->slug = $slug;
            }
        });
    }

    protected $fillable = ['product_category_id', 'slug', 'name', 'price', 'price_tiers', 'unit', 'description', 'image_url', 'images', 'rating', 'features', 'is_visible', 'sort_order', 'stock_quantity'];

    protected $appends = ['image', 'finishes'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'price_tiers' => 'array', 'rating' => 'decimal:1', 'features' => 'array', 'images' => 'array', 'is_visible' => 'boolean', 'sort_order' => 'integer', 'stock_quantity' => 'integer'];
    }

    public function getImageAttribute(): ?string
    {
        $imgs = array_filter($this->images ?? []);
        return array_values($imgs)[0] ?? $this->image_url;
    }

    /**
     * Resolve the unit price for a given tier label. Falls back to the base
     * price when the product has no tiers, or the label doesn't match one —
     * never trusts the label alone to determine a price out of thin air.
     */
    public function priceForTier(?string $label): float
    {
        $tiers = collect($this->price_tiers ?? [])->filter(fn ($t) => is_array($t) && isset($t['label']) && isset($t['price']));

        if ($tiers->isEmpty() || !$label) {
            return (float) $this->price;
        }

        $match = $tiers->first(fn ($t) => strcasecmp((string) $t['label'], $label) === 0);

        return $match ? (float) $match['price'] : (float) $this->price;
    }

    public function getFinishesAttribute(): array
    {
        if (! $this->relationLoaded('options')) {
            return [];
        }

        return $this->options->first()?->choices ?? [];
    }

    public function reduceStock(int $qty): void
    {
        if ($this->stock_quantity !== null) {
            $this->decrement('stock_quantity', $qty);
        }
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }
}
