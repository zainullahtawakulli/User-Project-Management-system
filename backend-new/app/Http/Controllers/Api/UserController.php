<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $currentUserId = $request->user()->id;

        $users = User::query()
            ->with('roleRelation:id,name,slug')
            ->where('id', '!=', $currentUserId)
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        );
                });
            })
            ->latest()
            ->get();

        return response()->json([
            'users' => $users,
        ]);
    }


    public function options(Request $request)
    {
        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereHas('roleRelation', fn ($roles) => $roles->where('slug', 'user'))
                    ->orWhere(function ($query) {
                        $query->whereNull('role_id')->where('role', 'user');
                    });
            })
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['pending', 'active', 'inactive'])],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        $role = isset($validated['role_id'])
            ? Role::findOrFail($validated['role_id'])
            : Role::where('slug', 'user')->firstOrFail();
        $this->ensureRoleAssignable($request->user(), $role);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
            'role_id' => $role->id,
        ]);

        $user->load('roleRelation:id,name,slug');

        return response()->json([
            'message' => 'User Created Successfully',
            'user' => $user,
        ], 201);
    }

    public function show(User $user)
    {
        $user->load('roleRelation:id,name,slug');

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'status' => [
                'required',
                Rule::in(['pending', 'active', 'inactive']),
            ],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
        ]);

        if (array_key_exists('role_id', $validated)) {
            $this->ensureRoleAssignable($request->user(), Role::findOrFail($validated['role_id']));
        }

        $user->update($validated);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['string', 'min:8', 'confirmed'],
            ]);
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        $user->load('roleRelation:id,name,slug');

        return response()->json([
            'message' => 'User Updated SuccessFully!',
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'User Deleted SuccessFully!',
        ]);
    }

    private function ensureRoleAssignable(User $actor, Role $role): void
    {
        if ($actor->role === 'super_admin') {
            return;
        }

        if ($role->slug === 'super_admin') {
            abort(403, 'Only a super admin can assign the super admin role.');
        }

        $missingPermissions = array_diff($role->permissions()->pluck('slug')->all(), $actor->permissions());
        abort_if($missingPermissions !== [], 403, 'You cannot assign a role with permissions you do not have.');
    }
}
