<?php

namespace App\Policies;

use App\Models\Copy;
use App\Models\Loan;
use App\Models\Media;
use App\Models\User;

class LoanPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdministrator() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Loan $loan): bool
    {
        $copy = $loan->copy;

        if (! $copy instanceof Copy) {
            return false;
        }

        $media = $copy->media;

        return $media instanceof Media
            && $user->can('view', $media)
            && $user->can('view', $loan->library);
    }

    public function create(User $user, Copy $copy): bool
    {
        return $copy->media !== null
            && $user->can('view', $copy->media)
            && $user->can('update', $copy);
    }

    public function markReturned(User $user, Loan $loan): bool
    {
        return $loan->isActive()
            && $user->can('update', $loan->copy);
    }

    public function update(User $user, Loan $loan): bool
    {
        return false;
    }

    public function delete(User $user, Loan $loan): bool
    {
        return false;
    }

    public function restore(User $user, Loan $loan): bool
    {
        return false;
    }

    public function forceDelete(User $user, Loan $loan): bool
    {
        return false;
    }
}
