<?php

namespace Tests\Feature;

use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_user_and_single_library_membership(): void
    {
        $this->artisan('minibib:user:create', [
            'email' => 'admin@example.test',
            '--name' => 'MiniBib Admin',
            '--password' => 'very-secure-password',
            '--admin' => true,
        ])->assertSuccessful();

        $user = User::query()
            ->where('email', 'admin@example.test')
            ->firstOrFail();

        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->is_active);
        $this->assertTrue(
            Hash::check('very-secure-password', $user->password),
        );

        $library = Library::query()
            ->where('slug', 'shared')
            ->where('type', Library::TYPE_SHARED)
            ->firstOrFail();

        $this->assertDatabaseCount('libraries', 1);

        $this->assertDatabaseHas('library_memberships', [
            'library_id' => $library->getKey(),
            'user_id' => $user->getKey(),
            'role' => LibraryMembership::ROLE_ADMIN,
        ]);
    }

    public function test_command_rejects_duplicate_email_address(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.test',
        ]);

        $this->artisan('minibib:user:create', [
            'email' => 'duplicate@example.test',
            '--name' => 'Duplicate',
            '--password' => 'very-secure-password',
        ])->assertFailed();

        $this->assertSame(
            1,
            User::query()
                ->where('email', 'duplicate@example.test')
                ->count(),
        );
    }
}
