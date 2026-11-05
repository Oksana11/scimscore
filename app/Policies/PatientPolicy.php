<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Data segregation: a user sees and changes only the patients they created.
 * Someone else's patient answers 404 (not 403), so its existence is not revealed.
 * Patients are never deleted through the application.
 */
class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Patient $patient): Response
    {
        return $user->is_active && $patient->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->institutionId() !== null;
    }

    public function update(User $user, Patient $patient): Response
    {
        return $this->view($user, $patient);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return false;
    }
}
