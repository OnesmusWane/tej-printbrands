<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = ['slug', 'title', 'excerpt', 'content', 'image_url', 'author', 'category', 'is_published', 'published_at', 'sort_order', 'read_time'];

    protected $casts = ['is_published' => 'boolean', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (BlogPost $post) {
            if (empty($post->slug)) {
                $base = Str::slug($post->title ?? 'post');
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $post->slug = $slug;
            }
        });
    }
}
