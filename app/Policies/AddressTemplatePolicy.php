<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AddressTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_address::template');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AddressTemplate $addressTemplate): bool
    {
        return $user->can('view_address::template');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_address::template');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AddressTemplate $addressTemplate): bool
    {
        return $user->can('update_address::template');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AddressTemplate $addressTemplate): bool
    {
        return $user->can('delete_address::template');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_address::template');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, AddressTemplate $addressTemplate): bool
    {
        return $user->can('force_delete_address::template');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_address::template');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, AddressTemplate $addressTemplate): bool
    {
        return $user->can('restore_address::template');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_address::template');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, AddressTemplate $addressTemplate): bool
    {
        return $user->can('replicate_address::template');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_address::template');
    }
}
