<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['product_category_id', 'slug', 'name', 'price', 'unit', 'description', 'image_url', 'rating', 'features', 'is_visible', 'sort_order'];

    protected $appends = ['image', 'finishes'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'rating' => 'decimal:1', 'features' => 'array', 'is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    public function getImageAttribute(): ?string
    {
        return $this->image_url;
    }

    public function getFinishesAttribute(): array
    {
        if (! $this->relationLoaded('options')) {
            return [];
        }

        return $this->options->first()?->choices ?? [];
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
