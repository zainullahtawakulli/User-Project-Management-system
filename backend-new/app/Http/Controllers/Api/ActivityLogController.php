<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'string', 'max:100'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
        $search = $filters['search'] ?? null;
        $event = $filters['event'] ?? null;
        $userIds = $filters['user_ids'] ?? [];

        $activities = Activity::query()
            ->with([
                'causer:id,name,email',
                'subject',
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%");
                });
            })
            ->when($event, function ($query, $event) {
                $query->where('event', $event);
            })
            ->when($userIds, function ($query, $userIds) {
                $query->whereIn('causer_id', $userIds)
                    ->where('causer_type', (new User())->getMorphClass());
            })
            ->when($filters['from_date'] ?? null, function ($query, $fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            })
            ->when($filters['to_date'] ?? null, function ($query, $toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            })
            ->latest()
            ->paginate(20);

        return response()->json($activities);
    }

    public function users()
    {
        $userIds = Activity::query()
            ->whereNotNull('causer_id')
            ->where('causer_type', (new User())->getMorphClass())
            ->distinct()
            ->pluck('causer_id');

        return response()->json([
            'users' => User::query()
                ->whereIn('id', $userIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }
}
