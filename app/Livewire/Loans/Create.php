<?php

namespace App\Livewire\Loans;

use App\Models\Borrower;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\Media;
use App\Models\User;
use App\Services\Loans\LoanManager;
use App\Support\ResolvesCurrentLibrary;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Create extends Component
{
    use ResolvesCurrentLibrary;

    public string $copyId = '';

    public string $borrowerId = '';

    public string $loanedAt = '';

    public string $dueAt = '';

    public string $notes = '';

    public function mount(?int $copyId = null): void
    {
        $library = $this->currentLibrary();
        Gate::authorize('update', $library);

        $this->loanedAt = now()->toDateString();
        $this->dueAt = now()->addWeeks(4)->toDateString();

        if ($copyId !== null) {
            $copy = $this->copy($copyId);
            Gate::authorize('create', [Loan::class, $copy]);
            $this->copyId = (string) $copyId;
        }
    }

    public function save(LoanManager $loanManager): void
    {
        $validated = $this->validate([
            'copyId' => ['required', 'integer'],
            'borrowerId' => ['required', 'integer'],
            'loanedAt' => ['required', 'date'],
            'dueAt' => ['nullable', 'date', 'after_or_equal:loanedAt'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $copy = $this->copy((int) $validated['copyId']);
        $borrower = $this->borrower((int) $validated['borrowerId']);

        Gate::authorize('create', [Loan::class, $copy]);

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $loanManager->create(
            copy: $copy,
            borrower: $borrower,
            user: $user,
            loanedAt: CarbonImmutable::parse((string) $validated['loanedAt'])->startOfDay(),
            dueAt: $validated['dueAt'] === ''
                ? null
                : CarbonImmutable::parse((string) $validated['dueAt'])->startOfDay(),
            notes: (string) $validated['notes'],
        );

        session()->flash('status', 'Ausleihe wurde erfasst.');

        $this->redirectRoute('loans.index');
    }

    public function render(): View
    {
        $library = $this->currentLibrary();
        Gate::authorize('update', $library);

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return view('livewire.loans.create', [
            'copies' => $this->loanableCopies($user),
            'borrowers' => Borrower::query()
                ->where('library_id', $library->getKey())
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function copy(int $copyId): Copy
    {
        return Copy::query()
            ->with('media')
            ->whereKey($copyId)
            ->where('library_id', $this->currentLibrary()->getKey())
            ->firstOrFail();
    }

    private function borrower(int $borrowerId): Borrower
    {
        return Borrower::query()
            ->whereKey($borrowerId)
            ->where('library_id', $this->currentLibrary()->getKey())
            ->firstOrFail();
    }

    /**
     * @return Collection<int, Copy>
     */
    private function loanableCopies(User $user): Collection
    {
        $library = $this->currentLibrary();

        return Copy::query()
            ->where('library_id', $library->getKey())
            ->where('status', Copy::STATUS_AVAILABLE)
            ->whereDoesntHave('loans', function (Builder $query): void {
                $query->whereNull('returned_at');
            })
            ->whereHas('media', function (Builder $query) use ($user): void {
                if ($user->isAdministrator()) {
                    return;
                }

                $query->where(function (Builder $query) use ($user): void {
                    $query->where('visibility', Media::VISIBILITY_SHARED)
                        ->orWhere('owner_user_id', $user->getKey());
                });
            })
            ->with('media')
            ->orderBy('inventory_code')
            ->orderBy('id')
            ->get();
    }
}
