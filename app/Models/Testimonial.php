<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'role', 'text', 'rating', 'status', 'submitted_at'];

    protected $appends = ['author'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'submitted_at' => 'datetime'];
    }

    public function getAuthorAttribute(): ?string
    {
        return $this->name;
    }
}
