<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    public function test_live_health_check_is_available(): void
    {
        $this->getJson('/health/live')
            ->assertOk();
    }

    public function test_ready_health_check_can_reach_database(): void
    {
        $this->getJson('/health/ready')
            ->assertOk()
            ->assertJson([
                'status' => 'ready',
                'database' => 'available',
            ]);
    }
}
