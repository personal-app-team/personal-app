<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PurposeAddressRule;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurposeAddressRulePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_purpose::address::rule');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurposeAddressRule $purposeAddressRule): bool
    {
        return $user->can('view_purpose::address::rule');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_purpose::address::rule');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurposeAddressRule $purposeAddressRule): bool
    {
        return $user->can('update_purpose::address::rule');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurposeAddressRule $purposeAddressRule): bool
    {
        return $user->can('delete_purpose::address::rule');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_purpose::address::rule');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, PurposeAddressRule $purposeAddressRule): bool
    {
        return $user->can('force_delete_purpose::address::rule');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_purpose::address::rule');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, PurposeAddressRule $purposeAddressRule): bool
    {
        return $user->can('restore_purpose::address::rule');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_purpose::address::rule');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, PurposeAddressRule $purposeAddressRule): bool
    {
        return $user->can('replicate_purpose::address::rule');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_purpose::address::rule');
    }
}
