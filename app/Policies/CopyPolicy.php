<?php

namespace App\Policies;

use App\Models\Copy;
use App\Models\Media;
use App\Models\User;

class CopyPolicy
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

    public function view(User $user, Copy $copy): bool
    {
        return $user->can('view', $copy->library);
    }

    public function create(User $user, Media $media): bool
    {
        return $user->can('update', $media->library);
    }

    public function update(User $user, Copy $copy): bool
    {
        return $user->can('update', $copy->library);
    }

    public function delete(User $user, Copy $copy): bool
    {
        return $user->can('update', $copy->library);
    }

    public function restore(User $user, Copy $copy): bool
    {
        return false;
    }

    public function forceDelete(User $user, Copy $copy): bool
    {
        return false;
    }
}
