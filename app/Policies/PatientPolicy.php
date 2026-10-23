<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can view any patient models.
     */
    public function viewAny(User $user): bool
    {
        return $user->roles()->count() === 0 || $user->can('view patients');
    }

    /**
     * Determine whether the user can view the patient model.
     */
    public function view(User $user, Patient $patient): bool
    {
        return $user->roles()->count() === 0 || $user->can('view patients');
    }

    /**
     * Determine whether the user can create patient models.
     */
    public function create(User $user): bool
    {
        return $user->roles()->count() === 0 || $user->can('create patients');
    }

    /**
     * Determine whether the user can update the patient model.
     */
    public function update(User $user, Patient $patient): bool
    {
        return $user->roles()->count() === 0 || $user->can('edit patients');
    }

    /**
     * Determine whether the user can delete the patient model.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin') || $user->can('delete patients');
    }
}
