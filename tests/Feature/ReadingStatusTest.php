<?php

namespace Tests\Feature;

use App\Livewire\Media\ReadingStatus;
use App\Models\Media;
use App\Models\MediaUserState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReadingStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_and_remove_personal_reading_status(): void
    {
        [$user, $media] = $this->context();

        $this->actingAs($user);

        Livewire::test(ReadingStatus::class, ['media' => $media])
            ->set('status', MediaUserState::STATUS_READING)
            ->set('startedAt', '2026-07-01')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('media_user_states', [
            'media_id' => $media->getKey(),
            'user_id' => $user->getKey(),
            'status' => MediaUserState::STATUS_READING,
            'started_at' => '2026-07-01',
        ]);

        Livewire::test(ReadingStatus::class, ['media' => $media])
            ->assertSet('status', MediaUserState::STATUS_READING)
            ->assertSet('startedAt', '2026-07-01')
            ->set('status', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('media_user_states', [
            'media_id' => $media->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_reading_status_is_stored_separately_for_each_user(): void
    {
        [$firstUser, $media, $secondUser] = $this->context();

        MediaUserState::query()->create([
            'media_id' => $media->getKey(),
            'user_id' => $firstUser->getKey(),
            'status' => MediaUserState::STATUS_FINISHED,
            'finished_at' => '2026-07-20',
        ]);

        $this->actingAs($secondUser);

        Livewire::test(ReadingStatus::class, ['media' => $media])
            ->assertSet('status', '')
            ->set('status', MediaUserState::STATUS_UNREAD)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('media_user_states', [
            'media_id' => $media->getKey(),
            'user_id' => $firstUser->getKey(),
            'status' => MediaUserState::STATUS_FINISHED,
        ]);

        $this->assertDatabaseHas('media_user_states', [
            'media_id' => $media->getKey(),
            'user_id' => $secondUser->getKey(),
            'status' => MediaUserState::STATUS_UNREAD,
        ]);
    }

    /**
     * @return array{User, Media, User}
     */
    private function context(): array
    {
        $firstUser = User::factory()->create([
            'is_active' => true,
        ]);

        $secondUser = User::factory()->create([
            'is_active' => true,
        ]);

        $this->addLibraryMember($firstUser);
        $this->addLibraryMember($secondUser);

        $media = $this->createMediaFor(
            $firstUser,
            'Lesestatus-Test',
        );

        return [$firstUser, $media, $secondUser];
    }
}
