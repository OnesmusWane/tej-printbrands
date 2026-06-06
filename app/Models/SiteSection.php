<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSection extends Model
{
    use HasFactory;

    protected $fillable = ['site_page_id', 'key', 'label', 'heading', 'subtext', 'image_url', 'settings', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'sort_order' => 'integer', 'is_published' => 'boolean'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SitePage::class, 'site_page_id');
    }
}
