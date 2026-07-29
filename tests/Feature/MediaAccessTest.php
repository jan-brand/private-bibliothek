<?php

namespace Tests\Feature;

use App\Livewire\Media\Index;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_shared_and_own_private_media_but_not_other_private_media(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember($user);
        $this->addLibraryMember($otherUser);

        $sharedMedia = $this->createMediaFor(
            $otherUser,
            'Gemeinsames Buch',
        );

        $ownPrivateMedia = $this->createMediaFor(
            $user,
            'Eigenes privates Buch',
            Media::VISIBILITY_PRIVATE,
        );

        $otherPrivateMedia = $this->createMediaFor(
            $otherUser,
            'Fremdes privates Buch',
            Media::VISIBILITY_PRIVATE,
        );

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee($sharedMedia->title)
            ->assertSee($ownPrivateMedia->title)
            ->assertDontSee($otherPrivateMedia->title);

        $this->assertTrue($user->can('view', $sharedMedia));
        $this->assertTrue($user->can('view', $ownPrivateMedia));
        $this->assertFalse($user->can('view', $otherPrivateMedia));
    }

    public function test_administrator_can_view_private_media_of_another_user(): void
    {
        $administrator = User::factory()->create([
            'is_active' => true,
            'is_admin' => true,
        ]);

        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember($owner);

        $media = $this->createMediaFor(
            $owner,
            'Administrativ sichtbar',
            Media::VISIBILITY_PRIVATE,
        );

        $this->assertTrue($administrator->can('view', $media));
    }
}
