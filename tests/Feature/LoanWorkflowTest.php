<?php

namespace Tests\Feature;

use App\Livewire\Loans\Create;
use App\Livewire\Loans\Index;
use App\Models\Borrower;
use App\Models\Copy;
use App\Models\LibraryMembership;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_copy_can_be_loaned_and_returned(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->addLibraryMember($user, LibraryMembership::ROLE_OWNER);
        $media = $this->createMediaFor($user);
        $borrower = Borrower::query()->create([
            'library_id' => $library->getKey(),
            'name' => 'Erika Beispiel',
        ]);
        $copy = $this->createCopy($user, $media->getKey());

        $this->actingAs($user);

        Livewire::test(Create::class, ['copyId' => $copy->getKey()])
            ->set('borrowerId', (string) $borrower->getKey())
            ->set('loanedAt', today()->toDateString())
            ->set('dueAt', today()->addWeeks(2)->toDateString())
            ->set('notes', 'Bitte pfleglich behandeln.')
            ->call('save')
            ->assertRedirect(route('loans.index'));

        $loan = Loan::query()->firstOrFail();

        $this->assertTrue($loan->isActive());
        $this->assertDatabaseHas('copies', [
            'id' => $copy->getKey(),
            'status' => Copy::STATUS_LOANED,
        ]);

        Livewire::test(Index::class)
            ->set("returnNotes.{$loan->getKey()}", 'Vollständig zurückgegeben.')
            ->call('returnLoan', $loan->getKey())
            ->assertHasNoErrors();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->getKey(),
            'return_notes' => 'Vollständig zurückgegeben.',
            'returned_by_user_id' => $user->getKey(),
        ]);
        $this->assertNotNull($loan->fresh()?->returned_at);
        $this->assertDatabaseHas('copies', [
            'id' => $copy->getKey(),
            'status' => Copy::STATUS_AVAILABLE,
        ]);
    }

    public function test_copy_cannot_have_two_active_loans(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->addLibraryMember($user, LibraryMembership::ROLE_OWNER);
        $media = $this->createMediaFor($user);
        $firstBorrower = Borrower::query()->create([
            'library_id' => $library->getKey(),
            'name' => 'Erste Person',
        ]);
        $secondBorrower = Borrower::query()->create([
            'library_id' => $library->getKey(),
            'name' => 'Zweite Person',
        ]);
        $copy = $this->createCopy($user, $media->getKey());

        $this->actingAs($user);

        Livewire::test(Create::class, ['copyId' => $copy->getKey()])
            ->set('borrowerId', (string) $firstBorrower->getKey())
            ->call('save')
            ->assertRedirect(route('loans.index'));

        Livewire::test(Create::class)
            ->set('copyId', (string) $copy->getKey())
            ->set('borrowerId', (string) $secondBorrower->getKey())
            ->call('save')
            ->assertHasErrors('copyId');

        $this->assertDatabaseCount('loans', 1);
    }

    public function test_missing_or_retired_copy_cannot_be_loaned(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->addLibraryMember($user, LibraryMembership::ROLE_OWNER);
        $media = $this->createMediaFor($user);
        $borrower = Borrower::query()->create([
            'library_id' => $library->getKey(),
            'name' => 'Erika Beispiel',
        ]);
        $copy = $this->createCopy(
            user: $user,
            mediaId: $media->getKey(),
            status: Copy::STATUS_RETIRED,
        );

        $this->actingAs($user);

        Livewire::test(Create::class)
            ->set('copyId', (string) $copy->getKey())
            ->set('borrowerId', (string) $borrower->getKey())
            ->call('save')
            ->assertHasErrors('copyId');

        $this->assertDatabaseCount('loans', 0);
    }

    private function createCopy(
        User $user,
        int $mediaId,
        string $status = Copy::STATUS_AVAILABLE,
    ): Copy {
        $library = $this->singleLibrary();

        return Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $mediaId,
            'condition' => Copy::CONDITION_GOOD,
            'status' => $status,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);
    }
}
