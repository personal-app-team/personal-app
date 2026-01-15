<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return false; // Разрешения создаются только через генератор
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return false; // Разрешения удаляются только через генератор
    }

    public function restore(User $user, Permission $permission): bool
    {
        return false;
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
        return false;
    }
}
