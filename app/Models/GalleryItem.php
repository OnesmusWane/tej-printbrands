<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'category', 'image_url', 'span', 'file_size', 'uploaded_at', 'is_visible', 'sort_order'];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime', 'is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    public function getUrlAttribute(): ?string
    {
        return $this->image_url;
    }
}
