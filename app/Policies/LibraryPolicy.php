<?php

namespace App\Policies;

use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\User;

class LibraryPolicy
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

    public function view(User $user, Library $library): bool
    {
        return $library->memberships()
            ->where('user_id', $user->getKey())
            ->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Library $library): bool
    {
        return $library->memberships()
            ->where('user_id', $user->getKey())
            ->whereIn('role', [
                LibraryMembership::ROLE_OWNER,
                LibraryMembership::ROLE_ADMIN,
            ])
            ->exists();
    }

    public function delete(User $user, Library $library): bool
    {
        return false;
    }

    public function restore(User $user, Library $library): bool
    {
        return false;
    }

    public function forceDelete(User $user, Library $library): bool
    {
        return false;
    }
}
