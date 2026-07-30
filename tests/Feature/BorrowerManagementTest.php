<?php

namespace Tests\Feature;

use App\Livewire\Borrowers\Index;
use App\Models\Borrower;
use App\Models\Copy;
use App\Models\LibraryMembership;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BorrowerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_owner_can_create_edit_and_delete_borrower(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->addLibraryMember($user, LibraryMembership::ROLE_OWNER);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('name', 'Erika Beispiel')
            ->set('email', 'erika@example.test')
            ->set('phone', '0123456789')
            ->call('save')
            ->assertHasNoErrors();

        $borrower = Borrower::query()->firstOrFail();

        $this->assertSame($library->getKey(), $borrower->library_id);

        Livewire::test(Index::class)
            ->call('edit', $borrower->getKey())
            ->set('name', 'Erika Muster')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('borrowers', [
            'id' => $borrower->getKey(),
            'name' => 'Erika Muster',
        ]);

        Livewire::test(Index::class)
            ->call('delete', $borrower->getKey())
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('borrowers', [
            'id' => $borrower->getKey(),
        ]);
    }

    public function test_borrower_with_loan_history_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->addLibraryMember($user, LibraryMembership::ROLE_OWNER);
        $media = $this->createMediaFor($user);
        $borrower = Borrower::query()->create([
            'library_id' => $library->getKey(),
            'name' => 'Max Beispiel',
        ]);
        $copy = Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $media->getKey(),
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        Loan::query()->create([
            'library_id' => $library->getKey(),
            'copy_id' => $copy->getKey(),
            'borrower_id' => $borrower->getKey(),
            'loaned_by_user_id' => $user->getKey(),
            'loaned_at' => now()->subDays(10),
            'due_at' => today()->subDays(3),
            'returned_at' => now()->subDay(),
            'returned_by_user_id' => $user->getKey(),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('delete', $borrower->getKey())
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('borrowers', [
            'id' => $borrower->getKey(),
        ]);
    }
}
