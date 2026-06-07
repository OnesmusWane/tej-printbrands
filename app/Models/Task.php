<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['task_column_id', 'project_id', 'title', 'priority', 'due_date', 'assignee', 'description', 'sort_order'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'sort_order' => 'integer'];
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(TaskColumn::class, 'task_column_id');
    }
}
