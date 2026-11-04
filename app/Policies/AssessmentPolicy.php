<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\Patient;
use App\Models\User;

/**
 * A user sees only their own assessments. Saved assessments and their
 * calculated results are immutable: no update, no delete.
 */
class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $user->is_active && $assessment->user_id !== null && $assessment->user_id === $user->id;
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
