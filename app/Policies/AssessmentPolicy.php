<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A user sees only their own assessments (404 otherwise). Saved assessments and
 * their calculated results are immutable: no update, no delete.
 */
class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Assessment $assessment): Response
    {
        return $user->is_active && $assessment->user_id !== null && $assessment->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user, ?Patient $patient = null): bool
    {
        return $user->is_active && ($patient === null || $patient->user_id === $user->id);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return false;
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return false;
    }
}
