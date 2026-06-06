<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Service $service) {
            if (empty($service->slug)) {
                $base = Str::slug($service->title ?? 'service');
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $service->slug = $slug;
            }
        });
    }

    protected $fillable = ['slug', 'title', 'description', 'icon', 'image_url', 'starting_price', 'features', 'sub_services', 'is_featured', 'is_visible', 'sort_order'];

    protected $appends = ['image', 'reverse'];

    protected function casts(): array
    {
        return ['features' => 'array', 'sub_services' => 'array', 'is_featured' => 'boolean', 'is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    public function getImageAttribute(): ?string
    {
        return $this->image_url;
    }

    public function getReverseAttribute(): bool
    {
        return $this->sort_order % 2 === 1;
    }
}
