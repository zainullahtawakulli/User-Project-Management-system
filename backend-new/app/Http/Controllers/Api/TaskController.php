<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $projectId = $request->input('project_id');

        $query = $this->visibleTasksQuery($request);
        $tasks = $query
            ->with([
                'project:id,name',
                'assignee:id,name,email',
                'assignees:id,name,email',
                'creator:id,name,email',
            ])
            ->when($projectId, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        $statusCounts = $this->visibleTasksQuery($request)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = [
            'total' => (int) $statusCounts->sum(),
            'todo' => (int) ($statusCounts['todo'] ?? 0),
            'in_progress' => (int) ($statusCounts['in_progress'] ?? 0),
            'review' => (int) ($statusCounts['review'] ?? 0),
            'completed' => (int) ($statusCounts['completed'] ?? 0),
        ];

        return response()->json(array_merge($tasks->toArray(), [
            'summary' => $summary,
        ]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
            ],

            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::in([
                    'todo',
                    'in_progress',
                    'review',
                    'completed',
                ]),
            ],

            'priority' => [
                'required',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                    'urgent',
                ]),
            ],

            'due_date' => [
                'nullable',
                'date',
            ],
        ]);

        $project = Project::findOrFail($validated['project_id']);

        /*
        |--------------------------------------------------------------------------
        | Make sure assigned user belongs to the project
        |--------------------------------------------------------------------------
        */

        $assigneeIds = $validated['assignee_ids'] ?? (empty($validated['assigned_to']) ? [] : [$validated['assigned_to']]);
        $memberIds = $project->users()->whereIn('users.id', $assigneeIds)->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        if (count($memberIds) !== count($assigneeIds)) {
            return response()->json(['message' => 'All assignees must be members of this project.'], 422);
        }

        $task = Task::create([
            'project_id' => $validated['project_id'],
            'assigned_to' => $assigneeIds[0] ?? null,
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $task->assignees()->sync($assigneeIds);

        $task->load([
            'project:id,name',
            'assignee:id,name,email',
            'assignees:id,name,email',
            'creator:id,name,email',
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'task' => $task,
        ], 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->ensureTaskIsVisible($request, $task);

        $task->load([
            'project:id,name',
            'assignee:id,name,email',
            'assignees:id,name,email',
            'creator:id,name,email',
        ]);

        return response()->json([
            'task' => $task,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $this->ensureTaskIsVisible($request, $task);

        if (! $request->user()->hasPermission('tasks.create')) {
            $validated = $request->validate([
                'status' => ['required', Rule::in(['todo', 'in_progress', 'review', 'completed'])],
            ]);

            $task->update(['status' => $validated['status']]);
            $task->load([
                'project:id,name',
                'assignee:id,name,email',
                'assignees:id,name,email',
                'creator:id,name,email',
            ]);

            return response()->json([
                'message' => 'Task status updated successfully.',
                'task' => $task,
            ]);
        }

        $validated = $request->validate([
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
            ],

            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::in([
                    'todo',
                    'in_progress',
                    'review',
                    'completed',
                ]),
            ],

            'priority' => [
                'required',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                    'urgent',
                ]),
            ],

            'due_date' => [
                'nullable',
                'date',
            ],
        ]);

        $project = Project::findOrFail($validated['project_id']);

        /*
        |--------------------------------------------------------------------------
        | Make sure assigned user belongs to the project
        |--------------------------------------------------------------------------
        */

        $assigneeIds = $validated['assignee_ids'] ?? (empty($validated['assigned_to']) ? [] : [$validated['assigned_to']]);
        $memberIds = $project->users()->whereIn('users.id', $assigneeIds)->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        if (count($memberIds) !== count($assigneeIds)) {
            return response()->json(['message' => 'All assignees must be members of this project.'], 422);
        }

        $task->update([
            'project_id' => $validated['project_id'],
            'assigned_to' => $assigneeIds[0] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $task->assignees()->sync($assigneeIds);

        $task->load([
            'project:id,name',
            'assignee:id,name,email',
            'assignees:id,name,email',
            'creator:id,name,email',
        ]);

        return response()->json([
            'message' => 'Task updated successfully.',
            'task' => $task,
        ]);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->ensureTaskIsVisible($request, $task);

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    private function visibleTasksQuery(Request $request)
    {
        $query = Task::query();
        if (in_array($request->user()->role, ['super_admin', 'admin'], true)) {
            return $query;
        }

        return $query->where(function ($query) use ($request) {
            $query->where('created_by', $request->user()->id)
                ->orWhere('assigned_to', $request->user()->id)
                ->orWhereHas('assignees', fn ($users) => $users->where('users.id', $request->user()->id))
                ->orWhereHas('project.users', fn ($users) => $users->where('users.id', $request->user()->id));
        });
    }

    private function ensureTaskIsVisible(Request $request, Task $task): void
    {
        if (in_array($request->user()->role, ['super_admin', 'admin'], true)) {
            return;
        }

        $isProjectMember = $task->project()->whereHas(
            'users',
            fn ($users) => $users->where('users.id', $request->user()->id)
        )->exists();
        $isAssignee = $task->assigned_to === $request->user()->id
            || $task->assignees()->where('users.id', $request->user()->id)->exists();

        abort_unless($isProjectMember || $isAssignee || $task->created_by === $request->user()->id, 404);
    }
}
