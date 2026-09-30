<?php

use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('/')->group(function () {

    Route::post('register', [
        AuthController::class,
        'register',
    ]);

    Route::post('login', [
        AuthController::class,
        'login',
    ]);

    Route::post('logout', [
        AuthController::class,
        'logout',
    ])->middleware('auth:sanctum');
});


/*
|--------------------------------------------------------------------------
| Protected API Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'active.user',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [
        ProfileController::class,
        'show',
    ]);

    Route::put('/profile', [
        ProfileController::class,
        'update',
    ]);

    Route::put('/profile/password', [
        ProfileController::class,
        'updatePassword',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Authenticated User
    |--------------------------------------------------------------------------
    */

    Route::get('/user', function (Request $request) {

        $user = $request->user()->load([
            'roleRelation.permissions',
        ]);

        $role = $user->roleRelation;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,

                /*
                |--------------------------------------------------------------------------
                | Role
                |--------------------------------------------------------------------------
                */

                'role' => $role?->slug ?? $role?->name,

                /*
                |--------------------------------------------------------------------------
                | Permissions
                |--------------------------------------------------------------------------
                */

                'permissions' => $role
                    ? $role->permissions
                    ->pluck('slug')
                    ->values()
                    ->toArray()
                    : [],
            ],
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    Route::get('/users', [
        UserController::class,
        'index',
    ])->middleware('permission:users.view');

    Route::post('/users', [
        UserController::class,
        'store',
    ])->middleware('permission:users.create');

    Route::get('/users/options', [
        UserController::class,
        'options',
    ])->middleware('permission:users.view');

    Route::get('/users/{user}', [
        UserController::class,
        'show',
    ])->middleware('permission:users.view');

    Route::put('/users/{user}', [
        UserController::class,
        'update',
    ])->middleware('permission:users.update');

    Route::delete('/users/{user}', [
        UserController::class,
        'destroy',
    ])->middleware('permission:users.delete');


    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions
    |--------------------------------------------------------------------------
    */

    Route::get('/roles/permissions', [
        RoleController::class,
        'permissions',
    ])->middleware('permission:roles.view');

    Route::get('/roles', [
        RoleController::class,
        'index',
    ])->middleware('permission:roles.view');

    Route::post('/roles', [
        RoleController::class,
        'store',
    ])->middleware('permission:roles.create');

    Route::get('/roles/{role}', [
        RoleController::class,
        'show',
    ])->middleware('permission:roles.view');

    Route::put('/roles/{role}', [
        RoleController::class,
        'update',
    ])->middleware('permission:roles.update');

    Route::delete('/roles/{role}', [
        RoleController::class,
        'destroy',
    ])->middleware('permission:roles.delete');


    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */

    Route::get('/projects', [
        ProjectController::class,
        'index',
    ])->middleware('permission:projects.view');

    Route::post('/projects', [
        ProjectController::class,
        'store',
    ])->middleware('permission:projects.create');

    Route::get('/projects/{project}', [
        ProjectController::class,
        'show',
    ])->middleware('permission:projects.view');

    Route::put('/projects/{project}', [
        ProjectController::class,
        'update',
    ])->middleware('permission:projects.update');

    Route::patch('/projects/{project}', [
        ProjectController::class,
        'update',
    ])->middleware('permission:projects.update');

    Route::delete('/projects/{project}', [
        ProjectController::class,
        'destroy',
    ])->middleware('permission:projects.delete');


    /*
    |--------------------------------------------------------------------------
    | Tasks
    |--------------------------------------------------------------------------
    */

    Route::get('/tasks', [
        TaskController::class,
        'index',
    ])->middleware('permission:tasks.view');

    Route::post('/tasks', [
        TaskController::class,
        'store',
    ])->middleware('permission:tasks.create');

    Route::get('/tasks/{task}', [
        TaskController::class,
        'show',
    ])->middleware('permission:tasks.view');

    Route::put('/tasks/{task}', [
        TaskController::class,
        'update',
    ])->middleware('permission:tasks.update');

    Route::patch('/tasks/{task}', [
        TaskController::class,
        'update',
    ])->middleware('permission:tasks.update');

    Route::delete('/tasks/{task}', [
        TaskController::class,
        'destroy',
    ])->middleware('permission:tasks.delete');
});
