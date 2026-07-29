<?php

namespace Tests;

use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function singleLibrary(): Library
    {
        return Library::query()->firstOrCreate(
            ['slug' => 'shared'],
            [
                'name' => 'MiniBib',
                'type' => Library::TYPE_SHARED,
                'owner_user_id' => null,
            ],
        );
    }

    protected function addLibraryMember(
        User $user,
        string $role = LibraryMembership::ROLE_MEMBER,
    ): Library {
        $library = $this->singleLibrary();

        LibraryMembership::query()->updateOrCreate(
            [
                'library_id' => $library->getKey(),
                'user_id' => $user->getKey(),
            ],
            [
                'role' => $role,
            ],
        );

        return $library;
    }

    protected function createMediaFor(
        User $owner,
        string $title = 'Testmedium',
        string $visibility = Media::VISIBILITY_SHARED,
    ): Media {
        $library = $this->singleLibrary();

        return Media::query()->create([
            'library_id' => $library->getKey(),
            'owner_user_id' => $owner->getKey(),
            'visibility' => $visibility,
            'type' => Media::TYPE_BOOK,
            'title' => $title,
            'created_by_user_id' => $owner->getKey(),
            'updated_by_user_id' => $owner->getKey(),
        ]);
    }
}
