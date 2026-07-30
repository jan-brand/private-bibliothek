<?php

namespace App\Policies;

use App\Models\Borrower;
use App\Models\Library;
use App\Models\User;

class BorrowerPolicy
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

    public function view(User $user, Borrower $borrower): bool
    {
        return $user->can('view', $borrower->library);
    }

    public function create(User $user, Library $library): bool
    {
        return $user->can('update', $library);
    }

    public function update(User $user, Borrower $borrower): bool
    {
        return $user->can('update', $borrower->library);
    }

    public function delete(User $user, Borrower $borrower): bool
    {
        return $user->can('update', $borrower->library);
    }

    public function restore(User $user, Borrower $borrower): bool
    {
        return false;
    }

    public function forceDelete(User $user, Borrower $borrower): bool
    {
        return false;
    }
}
