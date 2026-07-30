<?php

namespace App\Livewire\Borrowers;

use App\Models\Borrower;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    use ResolvesCurrentLibrary;

    public ?int $editingBorrowerId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    public string $search = '';

    public function save(): void
    {
        $library = $this->currentLibrary();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        if ($this->editingBorrowerId === null) {
            Gate::authorize('create', [Borrower::class, $library]);

            Borrower::query()->create([
                'library_id' => $library->getKey(),
                'name' => trim($validated['name']),
                'email' => $this->nullableString($validated['email']),
                'phone' => $this->nullableString($validated['phone']),
                'notes' => $this->nullableString($validated['notes']),
            ]);

            session()->flash('status', 'Entleihende Person wurde angelegt.');
        } else {
            $borrower = $this->borrower($this->editingBorrowerId);
            Gate::authorize('update', $borrower);

            $borrower->update([
                'name' => trim($validated['name']),
                'email' => $this->nullableString($validated['email']),
                'phone' => $this->nullableString($validated['phone']),
                'notes' => $this->nullableString($validated['notes']),
            ]);

            session()->flash('status', 'Entleihende Person wurde aktualisiert.');
        }

        $this->resetForm();
    }

    public function edit(int $borrowerId): void
    {
        $borrower = $this->borrower($borrowerId);
        Gate::authorize('update', $borrower);

        $this->editingBorrowerId = (int) $borrower->getKey();
        $this->name = $borrower->name;
        $this->email = (string) $borrower->email;
        $this->phone = (string) $borrower->phone;
        $this->notes = (string) $borrower->notes;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $borrowerId): void
    {
        $borrower = $this->borrower($borrowerId);
        Gate::authorize('delete', $borrower);

        if ($borrower->loans()->exists()) {
            $this->addError(
                'delete',
                'Eine Person mit Ausleihhistorie kann nicht gelöscht werden.',
            );

            return;
        }

        $borrower->delete();

        if ($this->editingBorrowerId === $borrowerId) {
            $this->resetForm();
        }

        session()->flash('status', 'Entleihende Person wurde gelöscht.');
    }

    public function render(): View
    {
        $library = $this->currentLibrary();
        Gate::authorize('update', $library);

        $search = trim($this->search);

        return view('livewire.borrowers.index', [
            'borrowers' => Borrower::query()
                ->where('library_id', $library->getKey())
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%")
                            ->orWhere('phone', 'ilike', "%{$search}%");
                    });
                })
                ->withCount(['loans', 'activeLoans'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function borrower(int $borrowerId): Borrower
    {
        return Borrower::query()
            ->whereKey($borrowerId)
            ->where('library_id', $this->currentLibrary()->getKey())
            ->firstOrFail();
    }

    private function resetForm(): void
    {
        $this->editingBorrowerId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->notes = '';
        $this->resetValidation();
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
