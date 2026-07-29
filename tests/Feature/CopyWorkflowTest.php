<?php

namespace Tests\Feature;

use App\Livewire\Copies\Edit;
use App\Models\Copy;
use App\Models\LibraryMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CopyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_copy_can_be_updated_and_deleted(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $library = $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_OWNER,
        );

        $media = $this->createMediaFor($user);

        $copy = Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $media->getKey(),
            'inventory_code' => 'ALT-1',
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        $copy->owners()->attach($user->getKey());

        $this->actingAs($user);

        Livewire::test(Edit::class, ['copy' => $copy])
            ->set('inventoryCode', 'NEU-1')
            ->set('condition', Copy::CONDITION_USED)
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('copies', [
            'id' => $copy->getKey(),
            'inventory_code' => 'NEU-1',
            'condition' => Copy::CONDITION_USED,
        ]);

        Livewire::test(Edit::class, ['copy' => $copy->fresh()])
            ->call('delete')
            ->assertRedirect(route('media.show', $media));

        $this->assertDatabaseMissing('copies', [
            'id' => $copy->getKey(),
        ]);
    }
}
