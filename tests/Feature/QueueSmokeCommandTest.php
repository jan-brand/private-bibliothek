<?php

namespace Tests\Feature;

use App\Jobs\QueueSmokeTest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class QueueSmokeCommandTest extends TestCase
{
    public function test_queue_smoke_command_dispatches_job(): void
    {
        Queue::fake();

        $exitCode = Artisan::call('minibib:queue-smoke');

        $this->assertSame(0, $exitCode);

        Queue::assertPushed(
            QueueSmokeTest::class,
            fn (QueueSmokeTest $job): bool => $job->token !== '',
        );
    }
}
