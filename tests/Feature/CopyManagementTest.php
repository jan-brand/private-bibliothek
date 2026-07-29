<?php

namespace Tests\Feature;

use App\Livewire\Copies\Create;
use App\Models\Copy;
use App\Models\LibraryMembership;
use App\Models\Location;
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

        $library = $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_ADMIN,
        );
        $this->addLibraryMember($secondOwner);

        $media = $this->createMediaFor($user);

        $location = Location::query()->create([
            'library_id' => $library->getKey(),
            'parent_id' => null,
            'type' => Location::TYPE_APARTMENT,
            'name' => 'Wohnung',
            'sort_order' => 0,
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->actingAs($user);

        Livewire::test(Create::class, ['mediaId' => $media->getKey()])
            ->set('inventoryCode', 'INV-0001')
            ->set('barcode', '1234567890')
            ->set('locationId', (string) $location->getKey())
            ->set('ownerUserIds', [$user->getKey(), $secondOwner->getKey()])
            ->call('save')
            ->assertRedirect();

        $copy = Copy::query()
            ->where('inventory_code', 'INV-0001')
            ->firstOrFail();

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

        $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_ADMIN,
        );

        $media = $this->createMediaFor($user);

        $this->actingAs($user);

        Livewire::test(Create::class, ['mediaId' => $media->getKey()])
            ->set('ownerUserIds', [$nonMember->getKey()])
            ->call('save')
            ->assertHasErrors('ownerUserIds');

        $this->assertDatabaseCount('copies', 0);
    }
}
