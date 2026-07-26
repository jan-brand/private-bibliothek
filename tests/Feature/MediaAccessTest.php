<?php

namespace Tests\Feature;

use App\Livewire\Media\Index;
use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_media_from_selected_accessible_library(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);

        $ownLibrary = $this->createPrivateLibrary($user, 'Eigene Bibliothek');
        $otherLibrary = $this->createPrivateLibrary($otherUser, 'Fremde Bibliothek');

        $ownMedia = $this->createMedia($ownLibrary, $user, 'Eigenes Buch');
        $otherMedia = $this->createMedia($otherLibrary, $otherUser, 'Fremdes Buch');

        $this->actingAs($user);
        session(['current_library_id' => $ownLibrary->getKey()]);

        Livewire::test(Index::class)
            ->assertSee($ownMedia->title)
            ->assertDontSee($otherMedia->title);

        $this->assertTrue($user->can('view', $ownMedia));
        $this->assertFalse($user->can('view', $otherMedia));
    }

    public function test_administrator_can_view_media_from_any_library(): void
    {
        $administrator = User::factory()->create([
            'is_active' => true,
            'is_admin' => true,
        ]);
        $owner = User::factory()->create(['is_active' => true]);
        $library = $this->createPrivateLibrary($owner, 'Private Bibliothek');
        $media = $this->createMedia($library, $owner, 'Administrativ sichtbar');

        $this->assertTrue($administrator->can('view', $media));
    }

    private function createPrivateLibrary(User $owner, string $name): Library
    {
        $library = Library::query()->create([
            'name' => $name,
            'slug' => 'private-'.$owner->getKey(),
            'type' => Library::TYPE_PRIVATE,
            'owner_user_id' => $owner->getKey(),
        ]);

        LibraryMembership::query()->create([
            'library_id' => $library->getKey(),
            'user_id' => $owner->getKey(),
            'role' => LibraryMembership::ROLE_OWNER,
        ]);

        return $library;
    }

    private function createMedia(Library $library, User $user, string $title): Media
    {
        return Media::query()->create([
            'library_id' => $library->getKey(),
            'type' => Media::TYPE_BOOK,
            'title' => $title,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);
    }
}
