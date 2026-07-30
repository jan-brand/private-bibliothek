<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\LibraryMembership;
use App\Models\Loan;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_member_cannot_manage_borrowers(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        $this->addLibraryMember($member, LibraryMembership::ROLE_MEMBER);

        $this->actingAs($member)
            ->get(route('borrowers.index'))
            ->assertForbidden();
    }

    public function test_private_copy_of_another_user_cannot_be_loaned_by_library_owner(): void
    {
        $libraryOwner = User::factory()->create(['is_active' => true]);
        $mediaOwner = User::factory()->create(['is_active' => true]);

        $library = $this->addLibraryMember(
            $libraryOwner,
            LibraryMembership::ROLE_OWNER,
        );
        $this->addLibraryMember($mediaOwner, LibraryMembership::ROLE_MEMBER);

        $media = $this->createMediaFor(
            owner: $mediaOwner,
            title: 'Privates Medium',
            visibility: Media::VISIBILITY_PRIVATE,
        );

        $copy = Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $media->getKey(),
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $mediaOwner->getKey(),
            'updated_by_user_id' => $mediaOwner->getKey(),
        ]);

        $this->assertFalse(
            $libraryOwner->can('create', [Loan::class, $copy]),
        );
    }

    public function test_administrator_can_manage_private_copy(): void
    {
        $administrator = User::factory()->create([
            'is_active' => true,
            'is_admin' => true,
        ]);
        $mediaOwner = User::factory()->create(['is_active' => true]);
        $library = $this->addLibraryMember(
            $mediaOwner,
            LibraryMembership::ROLE_MEMBER,
        );
        $media = $this->createMediaFor(
            owner: $mediaOwner,
            title: 'Privates Medium',
            visibility: Media::VISIBILITY_PRIVATE,
        );

        $copy = Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $media->getKey(),
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $mediaOwner->getKey(),
            'updated_by_user_id' => $mediaOwner->getKey(),
        ]);

        $this->assertTrue(
            $administrator->can('create', [Loan::class, $copy]),
        );
    }
}
