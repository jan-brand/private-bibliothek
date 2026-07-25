<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SystemStatus;
use Livewire\Livewire;
use Tests\TestCase;

final class SystemStatusTest extends TestCase
{
    public function test_component_displays_system_status(): void
    {
        Livewire::test(SystemStatus::class)
            ->assertStatus(200)
            ->assertSee('Systemstatus')
            ->assertSee('pgsql')
            ->assertSee('Bereit');
    }

    public function test_status_can_be_refreshed(): void
    {
        Livewire::test(SystemStatus::class)
            ->assertSet('refreshCount', 0)
            ->call('refreshStatus')
            ->assertSet('refreshCount', 1);
    }
}
