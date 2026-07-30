<?php

namespace App\Services\Loans;

use App\Models\Borrower;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanManager
{
    public function create(
        Copy $copy,
        Borrower $borrower,
        User $user,
        CarbonInterface $loanedAt,
        ?CarbonInterface $dueAt,
        ?string $notes,
    ): Loan {
        return DB::transaction(function () use (
            $borrower,
            $copy,
            $dueAt,
            $loanedAt,
            $notes,
            $user,
        ): Loan {
            $lockedCopy = Copy::query()
                ->lockForUpdate()
                ->findOrFail($copy->getKey());

            if ($lockedCopy->library_id !== $borrower->library_id) {
                throw ValidationException::withMessages([
                    'borrowerId' => 'Die entleihende Person gehört nicht zur Bibliothek des Exemplars.',
                ]);
            }

            if ($lockedCopy->status !== Copy::STATUS_AVAILABLE) {
                throw ValidationException::withMessages([
                    'copyId' => 'Nur verfügbare Exemplare können ausgeliehen werden.',
                ]);
            }

            if ($lockedCopy->activeLoan()->exists()) {
                throw ValidationException::withMessages([
                    'copyId' => 'Für dieses Exemplar besteht bereits eine aktive Ausleihe.',
                ]);
            }

            $loan = Loan::query()->create([
                'library_id' => $lockedCopy->library_id,
                'copy_id' => $lockedCopy->getKey(),
                'borrower_id' => $borrower->getKey(),
                'loaned_by_user_id' => $user->getKey(),
                'loaned_at' => $loanedAt,
                'due_at' => $dueAt,
                'notes' => $this->nullableString($notes),
            ]);

            $lockedCopy->update([
                'status' => Copy::STATUS_LOANED,
                'updated_by_user_id' => $user->getKey(),
            ]);

            return $loan;
        });
    }

    public function markReturned(
        Loan $loan,
        User $user,
        ?string $returnNotes,
    ): Loan {
        return DB::transaction(function () use ($loan, $returnNotes, $user): Loan {
            $lockedLoan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loan->getKey());

            if (! $lockedLoan->isActive()) {
                throw ValidationException::withMessages([
                    'return' => 'Diese Ausleihe wurde bereits zurückgegeben.',
                ]);
            }

            $copy = Copy::query()
                ->lockForUpdate()
                ->findOrFail($lockedLoan->copy_id);

            $lockedLoan->update([
                'returned_by_user_id' => $user->getKey(),
                'returned_at' => now(),
                'return_notes' => $this->nullableString($returnNotes),
            ]);

            $copy->update([
                'status' => Copy::STATUS_AVAILABLE,
                'updated_by_user_id' => $user->getKey(),
            ]);

            return $lockedLoan->fresh() ?? $lockedLoan;
        });
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
