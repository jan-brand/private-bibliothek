<?php

namespace Tests\Feature;

use App\Livewire\Locations\Index;
use App\Models\Copy;
use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\Location;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LocationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_can_be_renamed_and_empty_location_deleted(): void
    {
        [$user, $library] = $this->context();

        $location = $this->location($library, $user, 'Alt');

        $this->actingAs($user);
        session([
            'current_library_id' => $library->getKey(),
        ]);

        Livewire::test(Index::class)
            ->call('edit', $location->getKey())
            ->set('name', 'Neu')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('locations', [
            'id' => $location->getKey(),
            'name' => 'Neu',
        ]);

        Livewire::test(Index::class)
            ->call('delete', $location->getKey())
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('locations', [
            'id' => $location->getKey(),
        ]);
    }

    public function test_location_with_copy_cannot_be_deleted(): void
    {
        [$user, $library] = $this->context();

        $location = $this->location($library, $user, 'Belegt');

        $media = Media::query()->create([
            'library_id' => $library->getKey(),
            'type' => Media::TYPE_BOOK,
            'title' => 'Testmedium',
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $media->getKey(),
            'location_id' => $location->getKey(),
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        $this->actingAs($user);
        session([
            'current_library_id' => $library->getKey(),
        ]);

        Livewire::test(Index::class)
            ->call('delete', $location->getKey())
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('locations', [
            'id' => $location->getKey(),
        ]);
    }

    /**
     * @return array{User, Library}
     */
    private function context(): array
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $library = Library::query()->create([
            'name' => 'Private Bibliothek',
            'slug' => 'private-'.$user->getKey(),
            'type' => Library::TYPE_PRIVATE,
            'owner_user_id' => $user->getKey(),
        ]);

        LibraryMembership::query()->create([
            'library_id' => $library->getKey(),
            'user_id' => $user->getKey(),
            'role' => LibraryMembership::ROLE_OWNER,
        ]);

        return [$user, $library];
    }

    private function location(Library $library, User $user, string $name): Location
    {
        return Location::query()->create([
            'library_id' => $library->getKey(),
            'parent_id' => null,
            'type' => Location::TYPE_APARTMENT,
            'name' => $name,
            'sort_order' => 0,
            'created_by_user_id' => $user->getKey(),
        ]);
    }
}
