<?php

namespace Tests\Feature;

use App\Livewire\Media\Create;
use App\Livewire\Media\Import as ImportComponent;
use App\Models\Library;
use App\Models\User;
use App\Services\Metadata\DnbMetadataClient;
use App\Services\Metadata\MetadataResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class MetadataImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_can_be_previewed_and_applied_to_create_form(): void
    {
        [$user, $library] = $this->context();

        $result = new MetadataResult(
            source: 'dnb',
            sourceRecordId: '123456789',
            title: 'Importiertes Buch',
            subtitle: 'Eine Vorschau',
            creators: ['Beispiel, Erika'],
            publisher: 'Testverlag',
            publicationPlace: 'Berlin',
            publicationYear: 2026,
            edition: '2. Auflage',
            languageCode: 'ger',
            description: 'Importierte Beschreibung',
            identifiers: [
                'isbn' => '978-3-16-148410-0',
            ],
        );

        $this->mock(
            DnbMetadataClient::class,
            function (MockInterface $mock) use ($result): void {
                $mock->shouldReceive('searchByIsbn')
                    ->once()
                    ->with('978-3-16-148410-0')
                    ->andReturn($result);
            },
        );

        $this->actingAs($user);

        Livewire::test(ImportComponent::class)
            ->set('source', 'dnb')
            ->set('identifier', '9783161484100')
            ->call('formatIdentifier')
            ->assertSet('identifier', '978-3-16-148410-0')
            ->call('search')
            ->assertSet('result.title', 'Importiertes Buch')
            ->call('apply')
            ->assertRedirect(route('media.create'));

        Livewire::test(Create::class)
            ->assertSet('title', 'Importiertes Buch')
            ->assertSet('subtitle', 'Eine Vorschau')
            ->assertSet('creators', 'Beispiel, Erika')
            ->assertSet('publisher', 'Testverlag')
            ->assertSet('publicationYear', '2026')
            ->assertSet('isbn', '978-3-16-148410-0');

        $this->assertSame(
            $library->getKey(),
            $this->singleLibrary()->getKey(),
        );
    }

    public function test_isbn_with_any_check_digit_is_sent_to_dnb(): void
    {
        [$user] = $this->context();

        $this->mock(
            DnbMetadataClient::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('searchByIsbn')
                    ->once()
                    ->with('978-3-446-13139-1')
                    ->andReturn(null);
            },
        );

        $this->actingAs($user);

        Livewire::test(ImportComponent::class)
            ->set('source', 'dnb')
            ->set('identifier', '978-3-446-13139-1')
            ->call('search')
            ->assertHasNoErrors()
            ->assertSet(
                'errorMessage',
                'Zu dieser Kennung wurde kein Datensatz gefunden.',
            );
    }

    public function test_invalid_issn_is_rejected_before_request(): void
    {
        [$user] = $this->context();

        $this->actingAs($user);

        Livewire::test(ImportComponent::class)
            ->set('source', 'auto')
            ->set('identifier', '0028-0835')
            ->call('search')
            ->assertHasErrors(['identifier'])
            ->assertSet('result', null);
    }

    /**
     * @return array{User, Library}
     */
    private function context(): array
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $library = $this->addLibraryMember($user);

        return [$user, $library];
    }
}
