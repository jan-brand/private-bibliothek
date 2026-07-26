<?php

namespace Tests\Feature;

use App\Livewire\Media\Create;
use App\Livewire\Media\Edit;
use App\Models\Copy;
use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\Media;
use App\Models\MediaIdentifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_identifier_requires_confirmation(): void
    {
        [$user, $library] = $this->context();
        $existing = $this->media($library, $user, 'Vorhandenes Buch');

        MediaIdentifier::query()->create([
            'media_id' => $existing->getKey(),
            'scheme' => MediaIdentifier::SCHEME_ISBN,
            'value' => '978-3-16-148410-0',
            'normalized_value' => '9783161484100',
            'label' => 'ISBN',
        ]);

        $this->actingAs($user);
        session([
            'current_library_id' => $library->getKey(),
        ]);

        $component = Livewire::test(Create::class)
            ->set('title', 'Mögliche Dublette')
            ->set('isbn', '9783161484100')
            ->call('save')
            ->assertSet('duplicateMediaId', $existing->getKey())
            ->assertSet('duplicateConfirmed', false);

        $this->assertDatabaseCount('media', 1);

        $component
            ->call('confirmDuplicateAndSave')
            ->assertRedirect();

        $this->assertDatabaseCount('media', 2);
    }

    public function test_media_can_be_updated_and_deleted_without_copies(): void
    {
        [$user, $library] = $this->context();
        $media = $this->media($library, $user, 'Alter Titel');

        $this->actingAs($user);

        Livewire::test(Edit::class, ['media' => $media])
            ->set('title', 'Neuer Titel')
            ->set('publicationYear', '2024')
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('media', [
            'id' => $media->getKey(),
            'title' => 'Neuer Titel',
            'publication_year' => 2024,
        ]);

        Livewire::test(Edit::class, ['media' => $media->fresh()])
            ->call('delete')
            ->assertRedirect(route('media.index'));

        $this->assertDatabaseMissing('media', [
            'id' => $media->getKey(),
        ]);
    }

    public function test_media_with_copy_cannot_be_deleted(): void
    {
        [$user, $library] = $this->context();
        $media = $this->media($library, $user, 'Mit Exemplar');

        Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $media->getKey(),
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        $this->actingAs($user);

        Livewire::test(Edit::class, ['media' => $media])
            ->call('delete')
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('media', [
            'id' => $media->getKey(),
        ]);
    }

    /**
     * @return array{User, Library}
     */
    private function context(): array
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $library = Library::query()->create([
            'name' => 'Private Bibliothek',
            'slug' => 'private-'.$user->getKey(),
            'type' => Library::TYPE_PRIVATE,
            'owner_user_id' => $user->getKey(),
        ]);

        LibraryMembership::query()->create([
            'library_id' => $library->getKey(),
            'user_id' => $user->getKey(),
            'role' => LibraryMembership::ROLE_OWNER,
        ]);

        return [$user, $library];
    }

    private function media(Library $library, User $user, string $title): Media
    {
        return Media::query()->create([
            'library_id' => $library->getKey(),
            'type' => Media::TYPE_BOOK,
            'title' => $title,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);
    }
}
