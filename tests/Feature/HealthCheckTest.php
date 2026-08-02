<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    public function test_live_health_check_is_available(): void
    {
        $this->getJson('/health/live')
            ->assertOk();
    }

    public function test_ready_health_check_can_reach_required_services(): void
    {
        Storage::fake('local');

        $this->getJson('/health/ready')
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertJson([
                'status' => 'ready',
                'database' => 'available',
                'storage' => 'writable',
            ]);
    }

    public function test_ready_health_check_fails_when_database_is_unavailable(): void
    {
        Storage::fake('local');

        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andThrow(new RuntimeException('Database unavailable.'));

        $this->getJson('/health/ready')
            ->assertStatus(503)
            ->assertJson([
                'status' => 'not_ready',
                'database' => 'unavailable',
                'storage' => 'writable',
            ]);
    }

    public function test_ready_health_check_fails_when_private_storage_is_unavailable(): void
    {
        Storage::shouldReceive('disk')
            ->once()
            ->with('local')
            ->andThrow(new RuntimeException('Storage unavailable.'));

        $this->getJson('/health/ready')
            ->assertStatus(503)
            ->assertJson([
                'status' => 'not_ready',
                'database' => 'available',
                'storage' => 'unavailable',
            ]);
    }
}
