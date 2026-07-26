<?php

namespace Tests\Feature;

use App\Livewire\Copies\BarcodeScanner;
use App\Models\Copy;
use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\Media;
use App\Models\User;
use App\Support\IsbnDisplayFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BarcodeCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_isbn_barcode_is_normalized_for_storage_and_formatted_for_display(): void
    {
        [$user, $copy] = $this->context();

        $this->actingAs($user);

        Livewire::test(BarcodeScanner::class, ['copy' => $copy])
            ->set('barcode', ' 978-3-16-148410-0 ')
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('copies', [
            'id' => $copy->getKey(),
            'barcode' => '9783161484100',
        ]);

        Livewire::test(BarcodeScanner::class, ['copy' => $copy->fresh()])
            ->assertSet('barcode', '978-3-16-148410-0');
    }

    public function test_duplicate_barcode_is_rejected_within_library(): void
    {
        [$user, $copy, $media, $library] = $this->context();

        Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $media->getKey(),
            'barcode' => 'ABC123',
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        $this->actingAs($user);

        Livewire::test(BarcodeScanner::class, ['copy' => $copy])
            ->set('barcode', 'abc-123')
            ->call('save')
            ->assertHasErrors('barcode');

        $this->assertNull($copy->fresh()->barcode);
    }

    public function test_non_isbn_barcode_keeps_existing_normalization(): void
    {
        $this->assertSame(
            'CODE128ABC',
            IsbnDisplayFormatter::normalizeBarcode(' code-128 abc '),
        );
    }

    public function test_isbn_display_accepts_an_arbitrary_check_digit(): void
    {
        $this->assertSame(
            '978-3-446-13139-1',
            IsbnDisplayFormatter::format('9783446131391'),
        );
    }

    /**
     * @return array{User, Copy, Media, Library}
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

        $media = Media::query()->create([
            'library_id' => $library->getKey(),
            'type' => Media::TYPE_BOOK,
            'title' => 'Barcode-Test',
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        $copy = Copy::query()->create([
            'library_id' => $library->getKey(),
            'media_id' => $media->getKey(),
            'condition' => Copy::CONDITION_GOOD,
            'status' => Copy::STATUS_AVAILABLE,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
        ]);

        return [$user, $copy, $media, $library];
    }
}
