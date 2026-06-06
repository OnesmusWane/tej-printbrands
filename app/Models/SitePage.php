<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SitePage extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'title', 'subtitle', 'image_url', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SiteSection::class)->orderBy('sort_order');
    }
}
