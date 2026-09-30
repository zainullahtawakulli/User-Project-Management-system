<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'super_admin') {
            $projectQuery = Project::query()->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhereHas('users', fn ($users) => $users->where('users.id', $user->id));
            });

            $taskQuery = Task::query()->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhere('assigned_to', $user->id)
                    ->orWhereHas('assignees', fn ($users) => $users->where('users.id', $user->id))
                    ->orWhereHas('project.users', fn ($users) => $users->where('users.id', $user->id));
            });

            return response()->json([
                'role' => $user->role,
                'stats' => [
                    'total_projects' => (clone $projectQuery)->count(),
                    'total_tasks' => (clone $taskQuery)->count(),
                    'completed_tasks' => (clone $taskQuery)->where('status', 'completed')->count(),
                ],
            ]);
        }

        $stats = [
            'total' => User::count(),
            'pending' => User::where('status', 'pending')->count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
        ];

        $recentUsers = User::query()
            ->select('id', 'name', 'email', 'status')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'role' => $user->role,
            'stats' => $stats,
            'recent_users' => $recentUsers,
        ]);
    }
}
