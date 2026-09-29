<?php

namespace App\Policies;

use App\Models\SipPlan;
use App\Models\User;

class SipPlanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, \App\Models\Portfolio $portfolio): bool
    {
        return $user->id === $portfolio->user_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SipPlan $sipPlan): bool
    {
        return $user->id === $sipPlan->portfolio->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, \App\Models\Portfolio $portfolio): bool
    {
        return $user->id === $portfolio->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SipPlan $sipPlan): bool
    {
        return $user->id === $sipPlan->portfolio->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SipPlan $sipPlan): bool
    {
        return $user->id === $sipPlan->portfolio->user_id;
    }
}
