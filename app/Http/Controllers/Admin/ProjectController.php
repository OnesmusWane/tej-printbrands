<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ServiceBooking;
use App\Models\ServiceRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['tasks.column'])
            ->withCount(['bookings', 'serviceRequests'])
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('client', 'like', "%$s%")
                ->orWhere('owner', 'like', "%$s%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->month);
            $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'client'      => ['nullable', 'string', 'max:120'],
            'owner'       => ['nullable', 'string', 'max:120'],
            'status'      => ['sometimes', 'in:active,completed,on-hold'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date'],
        ]);

        $project = Project::create($data);

        return response()->json($project->load('tasks.column')->loadCount(['bookings', 'serviceRequests']), 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'client'      => ['nullable', 'string', 'max:120'],
            'owner'       => ['nullable', 'string', 'max:120'],
            'status'      => ['sometimes', 'in:active,completed,on-hold'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date'],
        ]);

        $project->update($data);

        return response()->json($project->fresh()->load('tasks.column')->loadCount(['bookings', 'serviceRequests']));
    }

    public function destroy(Project $project): JsonResponse
    {
        // Detach tasks from project rather than deleting them
        Task::where('project_id', $project->id)->update(['project_id' => null]);

        $project->delete();

        return response()->json(null, 204);
    }

    public function addJob(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:booking,service_request'],
            'id'   => ['required', 'integer'],
        ]);

        if ($data['type'] === 'booking') {
            ServiceBooking::where('id', $data['id'])->update(['project_id' => $project->id]);
        } else {
            ServiceRequest::where('id', $data['id'])->update(['project_id' => $project->id]);
        }

        return response()->json(['ok' => true]);
    }
}
