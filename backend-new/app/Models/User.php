<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'email', 'password', 'status', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // public function permissions(): array
    // {
    //     return match ($this->role) {
    //         'super_admin' => ['*'],
    //         'admin' => [
    //             'users.view',
    //             'users.create',
    //             'users.update',
    //             'users.delete',
    //             'projects.view',
    //             'projects.create',
    //             'projects.update',
    //             'projects.delete',
    //             'tasks.view',
    //             'tasks.create',
    //             'tasks.update',
    //             'tasks.delete',
    //         ],
    //         'user' => [
    //             'projects.view',
    //             'tasks.view',
    //             'tasks.update',
    //         ],
    //         default => [],
    //     };
    // }

    public function permissions(): array
    {
        $role = $this->assignedRole();

        return $role
            ? $role->permissions()->pluck('slug')->toArray()
            : [];
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->assignedRole();

        return $role?->permissions()
            ->where('slug', $permission)
            ->exists() ?? false;
    }

    public function getRoleAttribute($value): ?string
    {
        return $this->assignedRole()?->slug ?? $value;
    }

    private function assignedRole(): ?Role
    {
        if ($this->roleRelation) {
            return $this->roleRelation;
        }

        $legacyRole = $this->getRawOriginal('role');

        return $legacyRole
            ? Role::query()->where('slug', $legacyRole)->first()
            : null;
    }
}
