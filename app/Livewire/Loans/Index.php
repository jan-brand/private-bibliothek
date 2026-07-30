<?php

namespace App\Livewire\Loans;

use App\Models\Library;
use App\Models\Loan;
use App\Models\Media;
use App\Models\User;
use App\Services\Loans\LoanManager;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesCurrentLibrary;
    use WithPagination;

    public string $filter = 'active';

    public string $search = '';

    /**
     * @var array<int, string>
     */
    public array $returnNotes = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['active', 'overdue', 'returned', 'all'], true)) {
            return;
        }

        $this->filter = $filter;
        $this->resetPage();
    }

    public function returnLoan(int $loanId, LoanManager $loanManager): void
    {
        $loan = $this->loan($loanId);
        Gate::authorize('markReturned', $loan);

        $returnNotes = trim($this->returnNotes[$loanId] ?? '');

        if (mb_strlen($returnNotes) > 10000) {
            $this->addError(
                "returnNotes.{$loanId}",
                'Die Rückgabenotiz darf höchstens 10000 Zeichen enthalten.',
            );

            return;
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $loanManager->markReturned(
            loan: $loan,
            user: $user,
            returnNotes: $returnNotes,
        );

        unset($this->returnNotes[$loanId]);

        session()->flash('status', 'Rückgabe wurde erfasst.');
    }

    public function render(): View
    {
        $library = $this->currentLibrary();
        Gate::authorize('update', $library);

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $baseQuery = $this->visibleLoans($library, $user);

        $activeCount = (clone $baseQuery)->active()->count();
        $overdueCount = (clone $baseQuery)->overdue()->count();
        $returnedCount = (clone $baseQuery)->returned()->count();

        $loans = $this->applyFilter(clone $baseQuery)
            ->with([
                'borrower',
                'copy.media',
                'loanedBy',
                'returnedBy',
            ])
            ->orderByRaw('returned_at IS NOT NULL')
            ->orderBy('due_at')
            ->orderByDesc('loaned_at')
            ->paginate(20);

        return view('livewire.loans.index', [
            'loans' => $loans,
            'activeCount' => $activeCount,
            'overdueCount' => $overdueCount,
            'returnedCount' => $returnedCount,
        ]);
    }

    private function loan(int $loanId): Loan
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $this->visibleLoans($this->currentLibrary(), $user)
            ->with(['copy.media', 'library'])
            ->findOrFail($loanId);
    }

    /**
     * @return Builder<Loan>
     */
    private function visibleLoans(Library $library, User $user): Builder
    {
        $search = trim($this->search);

        return Loan::query()
            ->where('library_id', $library->getKey())
            ->whereHas('copy.media', function (Builder $query) use ($user): void {
                if ($user->isAdministrator()) {
                    return;
                }

                $query->where(function (Builder $query) use ($user): void {
                    $query->where('visibility', Media::VISIBILITY_SHARED)
                        ->orWhere('owner_user_id', $user->getKey());
                });
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('borrower', function (Builder $query) use ($search): void {
                        $query->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%");
                    })
                        ->orWhereHas('copy', function (Builder $query) use ($search): void {
                            $query->where('inventory_code', 'ilike', "%{$search}%")
                                ->orWhere('barcode', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('copy.media', function (Builder $query) use ($search): void {
                            $query->where('title', 'ilike', "%{$search}%");
                        });
                });
            });
    }

    /**
     * @param  Builder<Loan>  $query
     * @return Builder<Loan>
     */
    private function applyFilter(Builder $query): Builder
    {
        return match ($this->filter) {
            'overdue' => $query->overdue(),
            'returned' => $query->returned(),
            'all' => $query,
            default => $query->active(),
        };
    }
}
