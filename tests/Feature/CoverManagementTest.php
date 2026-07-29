<?php

namespace Tests\Feature;

use App\Livewire\Media\CoverManager;
use App\Models\LibraryMembership;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CoverManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cover_can_be_uploaded_and_removed(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        [$user, $media] = $this->context();

        $file = UploadedFile::fake()->createWithContent(
            'cover.png',
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z1V8AAAAASUVORK5CYII=',
                true,
            ),
        );

        $this->actingAs($user);

        Livewire::test(
            CoverManager::class,
            ['media' => $media],
        )
            ->set('upload', $file)
            ->call('storeUpload')
            ->assertHasNoErrors();

        $media->refresh();

        $path = (string) $media->getAttribute(
            'cover_path',
        );

        $this->assertNotSame('', $path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);

        Livewire::test(
            CoverManager::class,
            ['media' => $media],
        )
            ->call('removeLocal')
            ->assertHasNoErrors();

        $media->refresh();

        $this->assertNull(
            $media->getAttribute('cover_path'),
        );
        Storage::disk('local')->assertMissing($path);
    }

    public function test_remote_cover_can_be_stored_privately(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        Http::fake([
            'https://covers.example.test/book.png' => Http::response(
                'fake-image-content',
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        [$user, $media] = $this->context();

        $this->actingAs($user);

        Livewire::test(
            CoverManager::class,
            ['media' => $media],
        )
            ->set(
                'remoteUrl',
                'https://covers.example.test/book.png',
            )
            ->call('importRemote')
            ->assertHasNoErrors();

        $media->refresh();

        $path = (string) $media->getAttribute(
            'cover_path',
        );

        $this->assertSame(
            'https://covers.example.test/book.png',
            $media->getAttribute('cover_source_url'),
        );

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * @return array{User, Media}
     */
    private function context(): array
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_OWNER,
        );

        $media = $this->createMediaFor(
            $user,
            'Cover-Test',
            Media::VISIBILITY_PRIVATE,
        );

        return [$user, $media];
    }
}
