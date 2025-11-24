<?php

namespace App\Policies;

use App\Models\TraineeRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TraineeRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view_any_trainee_requests', 'view_own_trainee_requests']);
    }

    public function view(User $user, TraineeRequest $traineeRequest): bool
    {
        // Пользователь может просматривать свои запросы
        if ($user->id === $traineeRequest->user_id && $user->can('view_own_trainee_requests')) {
            return true;
        }

        // HR и Manager могут просматривать любые запросы
        return $user->can('view_any_trainee_requests');
    }

    public function create(User $user): bool
    {
        return $user->can('create_trainee_requests');
    }

    public function update(User $user, TraineeRequest $traineeRequest): bool
    {
        // Пользователь может редактировать только свои pending запросы
        if ($user->id === $traineeRequest->user_id && $traineeRequest->isPending()) {
            return $user->can('update_trainee_request');
        }

        return $user->can('manage_trainee_requests');
    }

    public function delete(User $user, TraineeRequest $traineeRequest): bool
    {
        // Пользователь может удалять только свои pending запросы
        if ($user->id === $traineeRequest->user_id && $traineeRequest->isPending()) {
            return $user->can('delete_trainee_request');
        }

        return $user->can('manage_trainee_requests');
    }

    public function approveHr(User $user, TraineeRequest $traineeRequest): bool
    {
        return $user->can('approve_trainee_requests_hr') && $traineeRequest->canBeApprovedByHr();
    }

    public function approveManager(User $user, TraineeRequest $traineeRequest): bool
    {
        return $user->can('approve_trainee_requests_manager') && $traineeRequest->canBeApprovedByManager();
    }

    public function makeDecision(User $user, TraineeRequest $traineeRequest): bool
    {
        // Решение может принимать инициатор запроса или manager
        $isInitiator = $user->id === $traineeRequest->user_id;
        return ($isInitiator || $user->can('make_trainee_decision')) && $traineeRequest->isActive();
    }
}
