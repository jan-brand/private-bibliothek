<?php

namespace Tests\Feature;

use App\Livewire\LibrarySwitcher;
use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LibraryAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_view_own_and_shared_libraries(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);

        $ownLibrary = $this->createPrivateLibrary($user, 'Eigene Bibliothek');
        $otherLibrary = $this->createPrivateLibrary($otherUser, 'Fremde Bibliothek');
        $sharedLibrary = $this->createSharedLibrary();

        LibraryMembership::query()->create([
            'library_id' => $sharedLibrary->getKey(),
            'user_id' => $user->getKey(),
            'role' => LibraryMembership::ROLE_MEMBER,
        ]);

        $this->assertTrue($user->can('view', $ownLibrary));
        $this->assertTrue($user->can('view', $sharedLibrary));
        $this->assertFalse($user->can('view', $otherLibrary));

        $this->actingAs($user);

        Livewire::test(LibrarySwitcher::class)
            ->assertSee('Eigene Bibliothek')
            ->assertSee('Gemeinsame Bibliothek')
            ->assertDontSee('Fremde Bibliothek');
    }

    public function test_administrator_can_view_all_libraries(): void
    {
        $administrator = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
        $owner = User::factory()->create(['is_active' => true]);
        $library = $this->createPrivateLibrary($owner, 'Private Bibliothek');

        $this->assertTrue($administrator->can('view', $library));
    }

    public function test_inactive_user_cannot_view_a_library(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $library = $this->createPrivateLibrary($user, 'Private Bibliothek');

        $this->assertFalse($user->can('view', $library));
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

    private function createSharedLibrary(): Library
    {
        return Library::query()->create([
            'name' => 'Gemeinsame Bibliothek',
            'slug' => 'shared',
            'type' => Library::TYPE_SHARED,
            'owner_user_id' => null,
        ]);
    }
}
