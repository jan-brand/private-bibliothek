<?php

namespace App\Livewire\Media;

use App\Models\Media;
use App\Models\MediaIdentifier;
use App\Services\Metadata\DnbMetadataClient;
use App\Services\Metadata\MetadataResult;
use App\Services\Metadata\ZdbMetadataClient;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class Import extends Component
{
    use ResolvesCurrentLibrary;

    public string $source = 'auto';

    public string $identifier = '';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $result = null;

    public string $errorMessage = '';

    public function mount(): void
    {
        Gate::authorize('create', [Media::class, $this->currentLibrary()]);
    }

    public function search(
        DnbMetadataClient $dnbClient,
        ZdbMetadataClient $zdbClient,
    ): void {
        Gate::authorize('create', [Media::class, $this->currentLibrary()]);

        $validated = $this->validate([
            'source' => ['required', Rule::in(['auto', 'dnb', 'zdb'])],
            'identifier' => ['required', 'string', 'max:64'],
        ]);

        $this->result = null;
        $this->errorMessage = '';

        try {
            $result = $this->lookup(
                $dnbClient,
                $zdbClient,
                $validated['source'],
                $validated['identifier'],
            );
        } catch (Throwable $exception) {
            Log::warning('Metadata lookup failed.', [
                'source' => $validated['source'],
                'identifier' => $validated['identifier'],
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $this->errorMessage = 'Der Metadatendienst ist derzeit nicht erreichbar.';

            return;
        }

        if ($this->getErrorBag()->has('identifier')) {
            return;
        }

        if ($result === null) {
            $this->errorMessage = 'Zu dieser Kennung wurde kein Datensatz gefunden.';

            return;
        }

        $this->result = $result->toArray();
    }

    public function apply(): void
    {
        Gate::authorize('create', [Media::class, $this->currentLibrary()]);

        if ($this->result === null) {
            $this->errorMessage = 'Bitte zuerst einen Datensatz suchen.';

            return;
        }

        session([
            'media_import' => $this->result,
        ]);

        $this->redirectRoute('media.create');
    }

    public function clearResult(): void
    {
        $this->result = null;
        $this->errorMessage = '';
    }

    public function render(): View
    {
        return view('livewire.media.import', [
            'library' => $this->currentLibrary(),
        ]);
    }

    private function lookup(
        DnbMetadataClient $dnbClient,
        ZdbMetadataClient $zdbClient,
        string $source,
        string $identifier,
    ): ?MetadataResult {
        $normalized = MediaIdentifier::normalize($identifier);

        if ($source === 'dnb') {
            if (! $this->isValidIsbn($normalized)) {
                $this->addError('identifier', 'Die ISBN ist ungültig.');

                return null;
            }

            return $dnbClient->searchByIsbn($identifier);
        }

        if ($source === 'zdb') {
            if (str_starts_with($normalized, 'ZDB')) {
                return $zdbClient->searchByZdbId($identifier);
            }

            if (! $this->isValidIssn($normalized)) {
                $this->addError(
                    'identifier',
                    'Für die ZDB bitte eine gültige ISSN oder ZDB-ID eingeben.',
                );

                return null;
            }

            return $zdbClient->searchByIssn($identifier);
        }

        if (str_starts_with($normalized, 'ZDB')) {
            return $zdbClient->searchByZdbId($identifier);
        }

        if (strlen($normalized) === 8) {
            if (! $this->isValidIssn($normalized)) {
                $this->addError('identifier', 'Die ISSN ist ungültig.');

                return null;
            }

            return $zdbClient->searchByIssn($identifier);
        }

        if (in_array(strlen($normalized), [10, 13], true)) {
            if (! $this->isValidIsbn($normalized)) {
                $this->addError('identifier', 'Die ISBN ist ungültig.');

                return null;
            }

            return $dnbClient->searchByIsbn($identifier);
        }

        $this->addError(
            'identifier',
            'Automatisch erkannt werden ISBN-10, ISBN-13, ISSN und ZDB-ID.',
        );

        return null;
    }

    private function isValidIsbn(string $identifier): bool
    {
        if (strlen($identifier) === 13 && ctype_digit($identifier)) {
            $sum = 0;

            for ($index = 0; $index < 12; $index++) {
                $digit = (int) $identifier[$index];
                $sum += $digit * ($index % 2 === 0 ? 1 : 3);
            }

            $expectedCheckDigit = (10 - ($sum % 10)) % 10;

            return $expectedCheckDigit === (int) $identifier[12];
        }

        if (
            strlen($identifier) === 10
            && ctype_digit(substr($identifier, 0, 9))
            && (ctype_digit($identifier[9]) || $identifier[9] === 'X')
        ) {
            $sum = 0;

            for ($index = 0; $index < 10; $index++) {
                $digit = $identifier[$index] === 'X'
                    ? 10
                    : (int) $identifier[$index];

                $sum += $digit * (10 - $index);
            }

            return $sum % 11 === 0;
        }

        return false;
    }

    private function isValidIssn(string $identifier): bool
    {
        if (
            strlen($identifier) !== 8
            || ! ctype_digit(substr($identifier, 0, 7))
            || (! ctype_digit($identifier[7]) && $identifier[7] !== 'X')
        ) {
            return false;
        }

        $sum = 0;

        for ($index = 0; $index < 7; $index++) {
            $sum += (int) $identifier[$index] * (8 - $index);
        }

        $remainder = 11 - ($sum % 11);
        $expected = match ($remainder) {
            10 => 'X',
            11 => '0',
            default => (string) $remainder,
        };

        return $expected === $identifier[7];
    }
}
