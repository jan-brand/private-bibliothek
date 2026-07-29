<?php

namespace Tests\Feature;

use App\Livewire\Lists\Index;
use App\Livewire\Lists\Show;
use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\Media;
use App\Models\MediaList;
use App\Models\MediaListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ListManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_private_list(): void
    {
        [$owner, , $library] = $this->context();

        $this->actingAs($owner);

        Livewire::test(Index::class)
            ->set('name', 'Als Nächstes lesen')
            ->set('description', 'Meine persönliche Auswahl')
            ->set('visibility', MediaList::VISIBILITY_PRIVATE)
            ->call('createList')
            ->assertRedirect();

        $this->assertDatabaseHas('media_lists', [
            'library_id' => $library->getKey(),
            'owner_user_id' => $owner->getKey(),
            'name' => 'Als Nächstes lesen',
            'visibility' => MediaList::VISIBILITY_PRIVATE,
        ]);
    }

    public function test_list_owner_can_add_reorder_and_remove_media(): void
    {
        [$owner, , $library, $firstMedia, $secondMedia] = $this->context();

        $mediaList = MediaList::query()->create([
            'library_id' => $library->getKey(),
            'owner_user_id' => $owner->getKey(),
            'name' => 'Reihenfolge',
            'visibility' => MediaList::VISIBILITY_SHARED,
        ]);

        $this->actingAs($owner);

        Livewire::test(Show::class, ['mediaList' => $mediaList])
            ->set('mediaId', (string) $firstMedia->getKey())
            ->call('addMedia')
            ->set('mediaId', (string) $secondMedia->getKey())
            ->call('addMedia');

        $firstItem = MediaListItem::query()
            ->where('media_list_id', $mediaList->getKey())
            ->where('media_id', $firstMedia->getKey())
            ->firstOrFail();

        $secondItem = MediaListItem::query()
            ->where('media_list_id', $mediaList->getKey())
            ->where('media_id', $secondMedia->getKey())
            ->firstOrFail();

        Livewire::test(Show::class, ['mediaList' => $mediaList])
            ->call('moveItem', $secondItem->getKey(), 'up');

        $firstItem->refresh();
        $secondItem->refresh();

        $this->assertSame(2, $firstItem->position);
        $this->assertSame(1, $secondItem->position);

        Livewire::test(Show::class, ['mediaList' => $mediaList])
            ->call('removeItem', $secondItem->getKey());

        $this->assertDatabaseMissing('media_list_items', [
            'id' => $secondItem->getKey(),
        ]);

        $firstItem->refresh();

        $this->assertSame(1, $firstItem->position);
    }

    public function test_shared_list_is_visible_but_private_list_is_hidden_from_other_member(): void
    {
        [$owner, $member, $library] = $this->context();

        $privateList = MediaList::query()->create([
            'library_id' => $library->getKey(),
            'owner_user_id' => $owner->getKey(),
            'name' => 'Privat',
            'visibility' => MediaList::VISIBILITY_PRIVATE,
        ]);

        $sharedList = MediaList::query()->create([
            'library_id' => $library->getKey(),
            'owner_user_id' => $owner->getKey(),
            'name' => 'Gemeinsam',
            'visibility' => MediaList::VISIBILITY_SHARED,
        ]);

        $this->assertFalse(
            Gate::forUser($member)->allows('view', $privateList),
        );

        $this->assertTrue(
            Gate::forUser($member)->allows('view', $sharedList),
        );

        $this->assertFalse(
            Gate::forUser($member)->allows('update', $sharedList),
        );
    }

    /**
     * @return array{User, User, Library, Media, Media}
     */
    private function context(): array
    {
        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'is_active' => true,
        ]);

        $library = $this->addLibraryMember(
            $owner,
            LibraryMembership::ROLE_OWNER,
        );

        $this->addLibraryMember($member);

        $firstMedia = $this->createMediaFor(
            $owner,
            'Erstes Buch',
        );

        $secondMedia = $this->createMediaFor(
            $owner,
            'Zweites Buch',
        );

        return [$owner, $member, $library, $firstMedia, $secondMedia];
    }
}
