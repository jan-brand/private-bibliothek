<?php

namespace App\Policies;

use App\Models\Library;
use App\Models\MediaList;
use App\Models\User;

class MediaListPolicy
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

    public function view(User $user, MediaList $mediaList): bool
    {
        $hasLibraryAccess = Library::query()
            ->accessibleTo($user)
            ->whereKey($mediaList->library_id)
            ->exists();

        if (! $hasLibraryAccess) {
            return false;
        }

        return $mediaList->visibility === MediaList::VISIBILITY_SHARED
            || $mediaList->isOwnedBy($user);
    }

    public function create(User $user, Library $library): bool
    {
        return $user->can('view', $library);
    }

    public function update(User $user, MediaList $mediaList): bool
    {
        return $mediaList->isOwnedBy($user);
    }

    public function delete(User $user, MediaList $mediaList): bool
    {
        return $mediaList->isOwnedBy($user);
    }

    public function restore(User $user, MediaList $mediaList): bool
    {
        return false;
    }

    public function forceDelete(User $user, MediaList $mediaList): bool
    {
        return false;
    }
}
