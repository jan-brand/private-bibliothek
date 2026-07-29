<?php

namespace Tests\Feature;

use App\Livewire\Media\Create;
use App\Livewire\Media\Edit;
use App\Models\Media;
use App\Models\MediaList;
use App\Models\MediaListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_private_media(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember($owner);

        $this->actingAs($owner);

        Livewire::test(Create::class)
            ->set('title', 'Direkt privat')
            ->set(
                'visibility',
                Media::VISIBILITY_PRIVATE,
            )
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('media', [
            'owner_user_id' => $owner->getKey(),
            'title' => 'Direkt privat',
            'visibility' => Media::VISIBILITY_PRIVATE,
        ]);
    }

    public function test_owner_can_change_shared_media_to_private(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember($owner);

        $media = $this->createMediaFor(
            $owner,
            'Sichtbarkeit ändern',
        );

        $this->actingAs($owner);

        Livewire::test(
            Edit::class,
            ['media' => $media],
        )
            ->set(
                'visibility',
                Media::VISIBILITY_PRIVATE,
            )
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('media', [
            'id' => $media->getKey(),
            'visibility' => Media::VISIBILITY_PRIVATE,
        ]);
    }

    public function test_shared_list_blocks_private_visibility(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $library = $this->addLibraryMember($owner);

        $media = $this->createMediaFor(
            $owner,
            'Auf gemeinsamer Liste',
        );

        $list = MediaList::query()->create([
            'library_id' => $library->getKey(),
            'owner_user_id' => $owner->getKey(),
            'name' => 'Gemeinsame Auswahl',
            'visibility' => MediaList::VISIBILITY_SHARED,
        ]);

        MediaListItem::query()->create([
            'media_list_id' => $list->getKey(),
            'media_id' => $media->getKey(),
            'position' => 1,
        ]);

        $this->actingAs($owner);

        Livewire::test(
            Edit::class,
            ['media' => $media],
        )
            ->set(
                'visibility',
                Media::VISIBILITY_PRIVATE,
            )
            ->call('save')
            ->assertHasErrors('visibility');

        $this->assertDatabaseHas('media', [
            'id' => $media->getKey(),
            'visibility' => Media::VISIBILITY_SHARED,
        ]);
    }

    public function test_other_member_cannot_open_private_media_or_edit_route(): void
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
            'Unsichtbares Medium',
            Media::VISIBILITY_PRIVATE,
        );

        $this->actingAs($member)
            ->get(route('media.show', $media))
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('media.edit', $media))
            ->assertForbidden();
    }
}
