<?php

namespace Tests\Feature;

use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SingleLibraryPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_database_contains_exactly_one_shared_library(): void
    {
        $library = $this->singleLibrary();

        $this->assertDatabaseCount('libraries', 1);
        $this->assertSame('shared', $library->slug);
        $this->assertSame(Library::TYPE_SHARED, $library->type);
        $this->assertNull($library->owner_user_id);
    }

    public function test_private_media_is_only_visible_to_its_owner_and_administrators(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create([
            'is_active' => true,
        ]);

        $administrator = User::factory()->create([
            'is_active' => true,
            'is_admin' => true,
        ]);

        $this->addLibraryMember($owner);
        $this->addLibraryMember($otherUser);
        $this->addLibraryMember(
            $administrator,
            LibraryMembership::ROLE_ADMIN,
        );

        $media = $this->createMediaFor(
            $owner,
            'Privates Buch',
            Media::VISIBILITY_PRIVATE,
        );

        $this->assertTrue(
            Gate::forUser($owner)->allows('view', $media),
        );

        $this->assertFalse(
            Gate::forUser($otherUser)->allows('view', $media),
        );

        $this->assertTrue(
            Gate::forUser($administrator)->allows('view', $media),
        );

        $this->assertSame(
            [$media->getKey()],
            Media::query()
                ->visibleTo($owner)
                ->pluck('id')
                ->all(),
        );

        $this->assertSame(
            [],
            Media::query()
                ->visibleTo($otherUser)
                ->pluck('id')
                ->all(),
        );
    }

    public function test_shared_media_is_visible_to_all_library_members(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember($owner);
        $this->addLibraryMember($member);

        $media = $this->createMediaFor(
            $owner,
            'Gemeinsames Buch',
        );

        $this->assertTrue(
            Gate::forUser($member)->allows('view', $media),
        );
    }

    public function test_only_owner_can_change_media_visibility(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember($owner);
        $this->addLibraryMember($member);

        $media = $this->createMediaFor(
            $owner,
            'Sichtbarkeitstest',
        );

        $this->assertTrue(
            Gate::forUser($owner)
                ->allows('changeVisibility', $media),
        );

        $this->assertFalse(
            Gate::forUser($member)
                ->allows('changeVisibility', $media),
        );
    }
}
