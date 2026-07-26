<?php

namespace Tests\Feature;

use App\Livewire\Copies\Create;
use App\Models\Copy;
use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\Location;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CopyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_owner_can_create_copy_with_location_and_owners(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $secondOwner = User::factory()->create(['is_active' => true]);
        $library = $this->createSharedLibrary($user, $secondOwner);
        $media = $this->createMedia($library, $user);
        $location = Location::query()->create([
            'library_id' => $library->getKey(),
            'parent_id' => null,
            'type' => Location::TYPE_APARTMENT,
            'name' => 'Wohnung',
            'sort_order' => 0,
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->actingAs($user);
        session(['current_library_id' => $library->getKey()]);

        Livewire::test(Create::class, ['mediaId' => $media->getKey()])
            ->set('inventoryCode', 'INV-0001')
            ->set('barcode', '1234567890')
            ->set('locationId', (string) $location->getKey())
            ->set('ownerUserIds', [$user->getKey(), $secondOwner->getKey()])
            ->call('save')
            ->assertRedirect();

        $copy = Copy::query()->where('inventory_code', 'INV-0001')->firstOrFail();

        $this->assertSame($location->getKey(), $copy->location_id);
        $this->assertDatabaseHas('copy_owners', [
            'copy_id' => $copy->getKey(),
            'user_id' => $user->getKey(),
        ]);
        $this->assertDatabaseHas('copy_owners', [
            'copy_id' => $copy->getKey(),
            'user_id' => $secondOwner->getKey(),
        ]);
    }

    public function test_non_member_cannot_be_selected_as_copy_owner(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $nonMember = User::factory()->create(['is_active' => true]);
        $library = $this->createSharedLibrary($user);
        $media = $this->createMedia($library, $user);

        $this->actingAs($user);
        session(['current_library_id' => $library->getKey()]);

        Livewire::test(Create::class, ['mediaId' => $media->getKey()])
            ->set('ownerUserIds', [$nonMember->getKey()])
            ->call('save')
            ->assertHasErrors('ownerUserIds');

        $this->assertDatabaseCount('copies', 0);
    }

    private function createSharedLibrary(User ...$members): Library
    {
        $library = Library::query()->create([
            'name' => 'Gemeinsame Bibliothek',
            'slug' => 'shared',
            'type' => Library::TYPE_SHARED,
            'owner_user_id' => null,
        ]);

        foreach ($members as $index => $member) {
            LibraryMembership::query()->create([
                'library_id' => $library->getKey(),
                'user_id' => $member->getKey(),
                'role' => $index === 0
                    ? LibraryMembership::ROLE_ADMIN
                    : LibraryMembership::ROLE_MEMBER,
            ]);
        }

        return $library;
    }

    private function createMedia(Library $library, User $user): Media
    {
        return Media::query()->create([
            'library_id' => $library->getKey(),
            'type' => Media::TYPE_BOOK,
            'title' => 'Testmedium',
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);
    }
}
