<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessStep extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'title', 'description', 'sort_order', 'is_visible'];

    protected $appends = ['num', 'desc'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_visible' => 'boolean'];
    }

    public function getNumAttribute(): ?string
    {
        return $this->number;
    }

    public function getDescAttribute(): ?string
    {
        return $this->description;
    }
}
