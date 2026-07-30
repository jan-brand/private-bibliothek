<?php

namespace Tests\Feature;

use App\Livewire\Media\Index;
use App\Models\Copy;
use App\Models\LibraryMembership;
use App\Models\Media;
use App\Models\MediaIdentifier;
use App\Models\User;
use App\Support\MediaCatalogSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_text_search_ranks_title_before_description_and_preserves_privacy(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);

        $library = $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_OWNER,
        );
        $this->addLibraryMember($otherUser);

        $titleMatch = $this->createMediaFor(
            $user,
            'Astronomie kompakt',
        );

        $descriptionMatch = $this->createMediaFor(
            $user,
            'Handbuch für Einsteiger',
        );
        $descriptionMatch->update([
            'description' => 'Astronomie und Sternenbeobachtung für den Einstieg.',
        ]);

        $privateMatch = $this->createMediaFor(
            $otherUser,
            'Geheime Astronomie',
            Media::VISIBILITY_PRIVATE,
        );

        $query = Media::query()
            ->forLibrary($library)
            ->visibleTo($user);

        app(MediaCatalogSearch::class)
            ->apply($query, 'Astronomie');

        $rankedIds = $query
            ->orderByDesc('search_rank')
            ->orderByRaw('coalesce(sort_title, title)')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $this->assertSame([
            (int) $titleMatch->getKey(),
            (int) $descriptionMatch->getKey(),
        ], $rankedIds);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'Astronomie')
            ->assertSee($titleMatch->title)
            ->assertSee($descriptionMatch->title)
            ->assertDontSee($privateMatch->title);
    }

    public function test_search_finds_identifier_barcode_and_inventory_code(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_OWNER,
        );
        $media = $this->createMediaFor(
            $user,
            'Kennungssuche',
        );

        MediaIdentifier::query()->create([
            'media_id' => $media->getKey(),
            'scheme' => MediaIdentifier::SCHEME_ISBN,
            'value' => '978-3-16-148410-0',
            'normalized_value' => '9783161484100',
            'label' => 'ISBN',
        ]);

        Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $media->getKey(),
            'inventory_code' => 'INV-042',
            'barcode' => 'ABC-987',
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', '978-3-16-148410-0')
            ->assertSee($media->title);

        Livewire::test(Index::class)
            ->set('search', 'INV 042')
            ->assertSee($media->title);

        Livewire::test(Index::class)
            ->set('search', 'ABC987')
            ->assertSee($media->title);
    }

    public function test_catalog_filters_limit_type_visibility_status_and_year(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_OWNER,
        );

        $matching = $this->createMediaFor(
            $user,
            'Passender Katalogeintrag',
            Media::VISIBILITY_PRIVATE,
        );
        $matching->update([
            'type' => Media::TYPE_BOOK,
            'publication_year' => 2024,
        ]);

        Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $matching->getKey(),
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        $wrongType = $this->createMediaFor(
            $user,
            'Falscher Medientyp',
        );
        $wrongType->update([
            'type' => Media::TYPE_BROCHURE,
            'publication_year' => 2024,
        ]);

        $wrongYear = $this->createMediaFor(
            $user,
            'Falsches Erscheinungsjahr',
            Media::VISIBILITY_PRIVATE,
        );
        $wrongYear->update([
            'type' => Media::TYPE_BOOK,
            'publication_year' => 1980,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('type', Media::TYPE_BOOK)
            ->set('visibility', Media::VISIBILITY_PRIVATE)
            ->set('copyStatus', Copy::STATUS_AVAILABLE)
            ->set('yearFrom', '2020')
            ->set('yearTo', '2026')
            ->assertSee($matching->title)
            ->assertDontSee($wrongType->title)
            ->assertDontSee($wrongYear->title);
    }

    public function test_search_vector_is_updated_when_catalog_data_changes(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_OWNER,
        );

        $media = $this->createMediaFor(
            $user,
            'Alte Bezeichnung',
        );

        $media->update([
            'title' => 'Quantenphysik verständlich',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'Quantenphysik')
            ->assertSee('Quantenphysik verständlich');

        Livewire::test(Index::class)
            ->set('search', 'Alte Bezeichnung')
            ->assertDontSee('Quantenphysik verständlich');
    }
}
