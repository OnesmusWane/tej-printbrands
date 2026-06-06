<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name', 'name', 'email', 'phone', 'subject', 'message', 'status', 'is_starred', 'reply', 'replied_at'];

    protected function casts(): array
    {
        return ['is_starred' => 'boolean', 'replied_at' => 'datetime'];
    }
}
