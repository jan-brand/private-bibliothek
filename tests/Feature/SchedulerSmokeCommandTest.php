<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SchedulerSmokeCommandTest extends TestCase
{
    public function test_scheduler_smoke_command_writes_status_file(): void
    {
        Storage::fake('local');

        $exitCode = Artisan::call('minibib:scheduler-smoke');

        $this->assertSame(0, $exitCode);

        Storage::disk('local')
            ->assertExists('health/scheduler-last-run.json');
    }
}
