<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

/**
 * Data segregation: a user sees and changes only the patients they created.
 * Patients are never deleted through the application.
 */
class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->is_active && $patient->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->institutionId() !== null;
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return false;
    }
}
