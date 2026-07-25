<?php

namespace App\Policies;

use App\Models\Library;
use App\Models\Media;
use App\Models\User;

class MediaPolicy
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

    public function view(User $user, Media $media): bool
    {
        return $user->can('view', $media->library);
    }

    public function create(User $user, Library $library): bool
    {
        return $user->can('update', $library);
    }

    public function update(User $user, Media $media): bool
    {
        return $user->can('update', $media->library);
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->can('update', $media->library);
    }

    public function restore(User $user, Media $media): bool
    {
        return false;
    }

    public function forceDelete(User $user, Media $media): bool
    {
        return false;
    }
}
