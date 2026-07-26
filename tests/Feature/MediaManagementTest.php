<?php

namespace Tests\Feature;

use App\Livewire\Media\Create;
use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\Media;
use App\Models\MediaIdentifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_owner_can_create_media_with_identifiers(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->createPrivateLibrary($user);

        $this->actingAs($user);
        session(['current_library_id' => $library->getKey()]);

        Livewire::test(Create::class)
            ->set('type', Media::TYPE_BOOK)
            ->set('title', 'Der Testkatalog')
            ->set('creators', 'Erika Beispiel')
            ->set('publisher', 'MiniBib Verlag')
            ->set('publicationYear', '2026')
            ->set('isbn', '978-3-16-148410-0')
            ->call('save')
            ->assertRedirect();

        $media = Media::query()
            ->where('library_id', $library->getKey())
            ->where('title', 'Der Testkatalog')
            ->firstOrFail();

        $this->assertDatabaseHas('media_identifiers', [
            'media_id' => $media->getKey(),
            'scheme' => MediaIdentifier::SCHEME_ISBN,
            'value' => '9783161484100',
            'normalized_value' => '9783161484100',
        ]);
    }

    public function test_unformatted_isbn_is_formatted_in_the_form_and_normalized_in_storage(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->createPrivateLibrary($user);

        $this->actingAs($user);
        session(['current_library_id' => $library->getKey()]);

        Livewire::test(Create::class)
            ->set('title', 'ISBN ohne Bindestriche')
            ->set('isbn', '9783161484100')
            ->call('formatIsbn')
            ->assertSet('isbn', '978-3-16-148410-0')
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('media_identifiers', [
            'scheme' => MediaIdentifier::SCHEME_ISBN,
            'value' => '9783161484100',
            'normalized_value' => '9783161484100',
        ]);
    }

    public function test_title_is_required_when_creating_media(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $library = $this->createPrivateLibrary($user);

        $this->actingAs($user);
        session(['current_library_id' => $library->getKey()]);

        Livewire::test(Create::class)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title' => 'required']);

        $this->assertDatabaseCount('media', 0);
    }

    private function createPrivateLibrary(User $owner): Library
    {
        $library = Library::query()->create([
            'name' => 'Private Bibliothek',
            'slug' => 'private-'.$owner->getKey(),
            'type' => Library::TYPE_PRIVATE,
            'owner_user_id' => $owner->getKey(),
        ]);

        LibraryMembership::query()->create([
            'library_id' => $library->getKey(),
            'user_id' => $owner->getKey(),
            'role' => LibraryMembership::ROLE_OWNER,
        ]);

        return $library;
    }
}
