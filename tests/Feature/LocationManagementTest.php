<?php

namespace Tests\Feature;

use App\Livewire\Locations\Index;
use App\Models\LibraryMembership;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_owner_can_create_location_hierarchy(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $library = $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_OWNER,
        );

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('type', Location::TYPE_APARTMENT)
            ->set('name', 'Hauptwohnung')
            ->call('save')
            ->assertHasNoErrors();

        $apartment = Location::query()
            ->where('library_id', $library->getKey())
            ->where('type', Location::TYPE_APARTMENT)
            ->firstOrFail();

        Livewire::test(Index::class)
            ->set('type', Location::TYPE_ROOM)
            ->set('name', 'Arbeitszimmer')
            ->set('parentId', (string) $apartment->getKey())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('locations', [
            'library_id' => $library->getKey(),
            'parent_id' => $apartment->getKey(),
            'type' => Location::TYPE_ROOM,
            'name' => 'Arbeitszimmer',
        ]);
    }

    public function test_location_rejects_wrong_parent_level(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $library = $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_OWNER,
        );

        $apartment = Location::query()->create([
            'library_id' => $library->getKey(),
            'parent_id' => null,
            'type' => Location::TYPE_APARTMENT,
            'name' => 'Wohnung',
            'sort_order' => 0,
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('type', Location::TYPE_SHELF)
            ->set('name', 'Regal A')
            ->set('parentId', (string) $apartment->getKey())
            ->call('save')
            ->assertHasErrors('parentId');

        $this->assertDatabaseMissing('locations', [
            'type' => Location::TYPE_SHELF,
            'name' => 'Regal A',
        ]);
    }
}
