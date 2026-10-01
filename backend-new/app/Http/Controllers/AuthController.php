<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create User
        |--------------------------------------------------------------------------
        */

        $role = Role::query()->where('slug', 'user')->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'role_id' => $role->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create Sanctum Token
        |--------------------------------------------------------------------------
        */

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        activity()
            ->causedBy($user)
            ->event('registered')
            ->log('User registered');

        /*
        |--------------------------------------------------------------------------
        | Return User
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Registration successful',

            'user' => $this->userResponse($user),

            'token' => $token,

            'token_type' => 'Bearer',
        ], 201);
    }


    /**
     * Login user.
     */
    public function login(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $user = User::where(
            'email',
            $validated['email']
        )->first();

        /*
        |--------------------------------------------------------------------------
        | Validate Credentials
        |--------------------------------------------------------------------------
        */

        if (
            ! $user ||
            ! Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Account Status
        |--------------------------------------------------------------------------
        */

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => [
                    'Your account is not active. Please contact the administrator.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Existing Tokens
        |--------------------------------------------------------------------------
        |
        | Optional, but this keeps one active token per user.
        |
        */

        $user->tokens()->delete();

        /*
        |--------------------------------------------------------------------------
        | Create New Token
        |--------------------------------------------------------------------------
        */

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        activity()
            ->causedBy($user)
            ->event('login')
            ->log('User logged in');

        /*
        |--------------------------------------------------------------------------
        | Return User
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Login successful',

            'user' => $this->userResponse($user),

            'token' => $token,

            'token_type' => 'Bearer',
        ]);
    }


    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Get Authenticated User
        |--------------------------------------------------------------------------
        */

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        activity()
            ->causedBy($user)
            ->event('logout')
            ->log('User logged out');

        /*
        |--------------------------------------------------------------------------
        | Delete Current Token
        |--------------------------------------------------------------------------
        */

        $user
            ->currentAccessToken()
            ?->delete();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }


    /**
     * Format authenticated user response.
     *
     * Roles and permissions are stored in the application's role tables.
     */
    private function userResponse(User $user): array
    {
        return [
            'id' => $user->id,

            'name' => $user->name,

            'email' => $user->email,

            'status' => $user->status,

            /*
            |--------------------------------------------------------------------------
            | Role
            |--------------------------------------------------------------------------
            */

            'role' => $user->role,

            /*
            |--------------------------------------------------------------------------
            | Permissions
            |--------------------------------------------------------------------------
            */

            'permissions' => $user->permissions(),
        ];
    }
}
