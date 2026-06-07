<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Task;

class Project extends Model
{
    protected $fillable = ['name', 'description', 'client', 'owner', 'status', 'start_date', 'end_date'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function jobsCount(): int
    {
        return $this->bookings()->count() + $this->serviceRequests()->count();
    }
}
