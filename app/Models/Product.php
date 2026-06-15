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

    protected $fillable = ['product_category_id', 'slug', 'name', 'price', 'unit', 'description', 'image_url', 'images', 'rating', 'features', 'is_visible', 'sort_order', 'stock_quantity'];

    protected $appends = ['image', 'finishes'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'rating' => 'decimal:1', 'features' => 'array', 'images' => 'array', 'is_visible' => 'boolean', 'sort_order' => 'integer', 'stock_quantity' => 'integer'];
    }

    public function getImageAttribute(): ?string
    {
        $imgs = array_filter($this->images ?? []);
        return array_values($imgs)[0] ?? $this->image_url;
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
