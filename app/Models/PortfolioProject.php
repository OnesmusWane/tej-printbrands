<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PortfolioProject extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (PortfolioProject $project) {
            if (empty($project->slug)) {
                $base = Str::slug($project->title ?? 'project');
                $slug = $base;
                $i    = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $project->slug = $slug;
            }
        });
    }

    protected $fillable = ['slug', 'title', 'category', 'client', 'project_date', 'image_url', 'description', 'services', 'gallery', 'is_featured', 'is_visible', 'sort_order', 'is_case_study', 'challenge', 'solution', 'results'];

    protected $appends = ['image', 'date'];

    protected function casts(): array
    {
        return ['services' => 'array', 'gallery' => 'array', 'results' => 'array', 'is_featured' => 'boolean', 'is_visible' => 'boolean', 'is_case_study' => 'boolean', 'sort_order' => 'integer'];
    }

    public function getImageAttribute(): ?string
    {
        return $this->image_url;
    }

    public function getDateAttribute(): ?string
    {
        return $this->project_date;
    }
}
