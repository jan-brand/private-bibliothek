<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProtectedCoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_private_cover_but_other_member_cannot(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember($owner);
        $this->addLibraryMember($member);

        $media = $this->createMediaFor(
            $owner,
            'Privates Cover',
            Media::VISIBILITY_PRIVATE,
        );

        $path = sprintf(
            'covers/%s/%s/test.png',
            $media->library_id,
            $media->getKey(),
        );

        Storage::disk('local')->put(
            $path,
            'protected-image',
        );

        $media->forceFill([
            'cover_path' => $path,
            'cover_mime_type' => 'image/png',
            'cover_updated_at' => now(),
        ])->save();

        $this->actingAs($owner)
            ->get(route('media.cover', $media))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff',
            );

        $this->actingAs($member)
            ->get(route('media.cover', $media))
            ->assertForbidden();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_shared_cover_is_available_to_library_member(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create([
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember($owner);
        $this->addLibraryMember($member);

        $media = $this->createMediaFor(
            $owner,
            'Gemeinsames Cover',
        );

        $path = sprintf(
            'covers/%s/%s/shared.png',
            $media->library_id,
            $media->getKey(),
        );

        Storage::disk('local')->put(
            $path,
            'shared-image',
        );

        $media->forceFill([
            'cover_path' => $path,
            'cover_mime_type' => 'image/png',
            'cover_updated_at' => now(),
        ])->save();

        $this->actingAs($member)
            ->get(route('media.cover', $media))
            ->assertOk();
    }
}
