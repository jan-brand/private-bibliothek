<?php

namespace Tests\Feature;

use App\Models\LibraryMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_can_view_the_single_library(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $library = $this->addLibraryMember($user);

        $this->assertDatabaseCount('libraries', 1);
        $this->assertTrue($user->can('view', $library));
    }

    public function test_active_non_member_cannot_view_the_single_library(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $library = $this->singleLibrary();

        $this->assertFalse($user->can('view', $library));
    }

    public function test_administrator_can_view_the_single_library_without_membership(): void
    {
        $administrator = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $library = $this->singleLibrary();

        $this->assertTrue($administrator->can('view', $library));
    }

    public function test_inactive_member_cannot_view_the_single_library(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $library = $this->addLibraryMember(
            $user,
            LibraryMembership::ROLE_MEMBER,
        );

        $this->assertFalse($user->can('view', $library));
    }
}
