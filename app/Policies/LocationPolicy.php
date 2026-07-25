<?php

namespace App\Policies;

use App\Models\Library;
use App\Models\Location;
use App\Models\User;

class LocationPolicy
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

    public function view(User $user, Location $location): bool
    {
        return $user->can('view', $location->library);
    }

    public function create(User $user, Library $library): bool
    {
        return $user->can('update', $library);
    }

    public function update(User $user, Location $location): bool
    {
        return $user->can('update', $location->library);
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->can('update', $location->library);
    }

    public function restore(User $user, Location $location): bool
    {
        return false;
    }

    public function forceDelete(User $user, Location $location): bool
    {
        return false;
    }
}
