<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->withCount('users')
            ->with('permissions:id,name,slug,group')
            ->orderBy('name')
            ->get();

        return response()->json([
            'roles' => $roles,
        ]);
    }

    public function show(Role $role)
    {
        $role->load(
            'permissions:id,name,slug,group'
        );

        return response()->json([
            'role' => $role,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                'unique:roles,slug',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'permission_ids' => [
                'nullable',
                'array',
            ],

            'permission_ids.*' => [
                'integer',
                'distinct',
                'exists:permissions,id',
            ],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync(
            $validated['permission_ids'] ?? []
        );

        $role->load('permissions');

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $role,
        ], 201);
    }

    public function update(
        Request $request,
        Role $role
    ) {
        if ($role->slug === 'super_admin') {
            return response()->json([
                'message' => 'The super admin role is protected and cannot be changed.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('roles', 'slug')
                    ->ignore($role->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'permission_ids' => [
                'nullable',
                'array',
            ],

            'permission_ids.*' => [
                'integer',
                'distinct',
                'exists:permissions,id',
            ],
        ]);

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync(
            $validated['permission_ids'] ?? []
        );

        $role->load('permissions');

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => $role,
        ]);
    }

    public function destroy(Role $role)
    {
        if ($role->slug === 'super_admin') {
            return response()->json([
                'message' => 'The super admin role is protected and cannot be deleted.',
            ], 403);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'This role is assigned to users and cannot be deleted.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }

    public function permissions()
    {
        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        return response()->json([
            'permissions' => $permissions,
        ]);
    }
}
