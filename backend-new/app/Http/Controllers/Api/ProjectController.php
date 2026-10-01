<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $search = $request->input('search');
        $perPage = $validated['per_page'] ?? 10;
        $query = Project::query();
        if (! in_array($request->user()->role, ['super_admin', 'admin'], true)) {
            $query->where(function ($query) use ($request) {
                $query->where('created_by', $request->user()->id)
                    ->orWhereHas('users', fn ($users) => $users->where('users.id', $request->user()->id));
            });
        }

        $projects = $query->with([
            'creator:id,name,email',
        ])->withCount('users')->withCount([
            'tasks' => function ($tasks) use ($request) {
                if (! $request->user()->hasPermission('tasks.create')) {
                    $tasks->where(function ($assigned) use ($request) {
                        $assigned->where('assigned_to', $request->user()->id)
                            ->orWhereHas('assignees', fn ($users) => $users->where('users.id', $request->user()->id));
                    });
                }
            },
        ])->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
            });
        })->latest()->paginate($perPage);

        return response()->json($projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'string'],

            'status' => ['required', Rule::in(['active', 'completed', 'on_hold', 'cancelled'])],

            'start_date' => ['nullable', 'date'],

            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'user_ids' => ['nullable', 'array'],

            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'start_date' => $validated['start_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        if (! empty($validated['user_ids'])) {
            $project->users()->sync($validated['user_ids']);
        }

        $project->load([
            'creator:id,name,email',
            'users:id,name,email',
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $project,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project)
    {
        $this->ensureProjectIsVisible($request, $project);

        $project->load([
            'creator:id,name,email',
            'users:id,name,email',
            'tasks' => function ($query) use ($request) {
                if (! $request->user()->hasPermission('tasks.create')) {
                    $query->where(function ($assigned) use ($request) {
                        $assigned->where('assigned_to', $request->user()->id)
                            ->orWhereHas('assignees', fn ($users) => $users->where('users.id', $request->user()->id));
                    });
                }
            },
            'tasks.assignee:id,name,email',
            'tasks.assignees:id,name,email',
            'tasks.creator:id,name,email',
        ]);

        return response()->json([
            'project' => $project,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        Project $project
    ) {
        $this->ensureProjectIsVisible($request, $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'string'],

            'status' => ['required',  Rule::in(['active', 'completed', 'on_hold', 'cancelled'])],

            'start_date' => ['nullable', 'date'],

            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'user_ids' => ['nullable', 'array'],

            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $project->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'start_date' => $validated['start_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $project->users()->sync(
            $validated['user_ids'] ?? []
        );

        $project->load([
            'creator:id,name,email',
            'users:id,name,email',
        ]);

        return response()->json([
            'message' => 'Project updated successfully.',
            'project' => $project,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Project $project)
    {
        $this->ensureProjectIsVisible($request, $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    private function ensureProjectIsVisible(Request $request, Project $project): void
    {
        if (in_array($request->user()->role, ['super_admin', 'admin'], true)) {
            return;
        }

        $isMember = $project->users()->where('users.id', $request->user()->id)->exists();
        abort_unless($isMember || $project->created_by === $request->user()->id, 404);
    }
}
