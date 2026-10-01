<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $user = $request->user();
        $term = trim($validated['q']);
        $pattern = "%{$term}%";
        $results = [];

        if ($user->hasPermission('users.view')) {
            $users = User::query()
                ->where(function ($query) use ($pattern) {
                    $query->where('name', 'like', $pattern)
                        ->orWhere('email', 'like', $pattern);
                })
                ->orderBy('name')
                ->limit(6)
                ->get(['id', 'name', 'email']);

            foreach ($users as $match) {
                $results[] = [
                    'type' => 'User',
                    'id' => $match->id,
                    'title' => $match->name,
                    'subtitle' => $match->email,
                    'url' => "/user/{$match->id}",
                ];
            }
        }

        if ($user->hasPermission('projects.view')) {
            $projects = Project::query()
                ->when(! in_array($user->role, ['super_admin', 'admin'], true), function ($query) use ($user) {
                    $query->where(function ($visible) use ($user) {
                        $visible->where('created_by', $user->id)
                            ->orWhereHas('users', fn ($members) => $members->where('users.id', $user->id));
                    });
                })
                ->where(function ($query) use ($pattern) {
                    $query->where('name', 'like', $pattern)
                        ->orWhere('description', 'like', $pattern);
                })
                ->orderBy('name')
                ->limit(6)
                ->get(['id', 'name', 'description', 'status']);

            foreach ($projects as $match) {
                $results[] = [
                    'type' => 'Project',
                    'id' => $match->id,
                    'title' => $match->name,
                    'subtitle' => ucfirst(str_replace('_', ' ', $match->status)),
                    'url' => "/projects/{$match->id}",
                ];
            }
        }

        if ($user->hasPermission('tasks.view')) {
            $tasks = Task::query()
                ->with('project:id,name')
                ->when(! $user->hasPermission('tasks.create'), function ($query) use ($user) {
                    $query->where(function ($visible) use ($user) {
                        $visible->where('assigned_to', $user->id)
                            ->orWhereHas('assignees', fn ($assignees) => $assignees->where('users.id', $user->id));
                    });
                })
                ->where(function ($query) use ($pattern) {
                    $query->where('title', 'like', $pattern)
                        ->orWhere('description', 'like', $pattern);
                })
                ->orderBy('title')
                ->limit(6)
                ->get(['id', 'title', 'description', 'status', 'project_id']);

            foreach ($tasks as $match) {
                $results[] = [
                    'type' => 'Task',
                    'id' => $match->id,
                    'title' => $match->title,
                    'subtitle' => ($match->project?->name ? $match->project->name.' · ' : '').ucfirst(str_replace('_', ' ', $match->status)),
                    'url' => "/tasks/{$match->id}",
                ];
            }
        }

        if ($user->hasPermission('activity_logs.view')) {
            $activities = Activity::query()
                ->with('causer:id,name')
                ->where(function ($query) use ($pattern) {
                    $query->where('description', 'like', $pattern)
                        ->orWhere('event', 'like', $pattern);
                })
                ->latest()
                ->limit(6)
                ->get(['id', 'description', 'event', 'causer_id', 'created_at']);

            foreach ($activities as $match) {
                $results[] = [
                    'type' => 'Activity',
                    'id' => $match->id,
                    'title' => $match->description,
                    'subtitle' => $match->causer?->name ?? ucfirst((string) $match->event),
                    'url' => '/activity-logs',
                ];
            }
        }

        return response()->json(['results' => $results]);
    }
}
