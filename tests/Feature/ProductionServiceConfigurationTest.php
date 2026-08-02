<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ProductionServiceConfigurationTest extends TestCase
{
    public function test_queue_worker_service_uses_safe_production_settings(): void
    {
        $content = $this->readProjectFile('deploy/systemd/minibib-queue.service');

        $this->assertStringContainsString('User=minibib', $content);
        $this->assertStringContainsString('Group=www-data', $content);
        $this->assertStringContainsString('WorkingDirectory=/srv/minibib/current', $content);
        $this->assertStringContainsString(
            'ExecStart=/usr/bin/php artisan queue:work database',
            $content,
        );
        $this->assertStringContainsString('--queue=default', $content);
        $this->assertStringContainsString('--tries=3', $content);
        $this->assertStringContainsString('--timeout=60', $content);
        $this->assertStringContainsString('--max-time=3600', $content);
        $this->assertStringContainsString('ExecReload=/usr/bin/php artisan queue:restart', $content);
        $this->assertStringContainsString('Restart=always', $content);
        $this->assertStringContainsString('NoNewPrivileges=true', $content);
        $this->assertStringNotContainsString('User=root', $content);
    }

    public function test_scheduler_uses_a_minutely_systemd_timer(): void
    {
        $service = $this->readProjectFile('deploy/systemd/minibib-scheduler.service');
        $timer = $this->readProjectFile('deploy/systemd/minibib-scheduler.timer');

        $this->assertStringContainsString(
            'ExecStart=/usr/bin/php artisan schedule:run',
            $service,
        );
        $this->assertStringNotContainsString('schedule:work', $service);
        $this->assertStringContainsString('Type=oneshot', $service);
        $this->assertStringContainsString('User=minibib', $service);
        $this->assertStringContainsString('NoNewPrivileges=true', $service);

        $this->assertStringContainsString('OnCalendar=*-*-* *:*:00', $timer);
        $this->assertStringContainsString('Persistent=true', $timer);
        $this->assertStringContainsString(
            'Unit=minibib-scheduler.service',
            $timer,
        );
        $this->assertStringContainsString('WantedBy=timers.target', $timer);
    }

    #[DataProvider('productionFileProvider')]
    public function test_production_service_files_do_not_contain_secrets(
        string $relativePath,
    ): void {
        $content = $this->readProjectFile($relativePath);

        $this->assertStringNotContainsString('DB_PASSWORD=', $content);
        $this->assertStringNotContainsString('APP_KEY=', $content);
        $this->assertStringNotContainsString('PASSWORD', $content);
        $this->assertStringNotContainsString('base64:', $content);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function productionFileProvider(): array
    {
        return [
            'queue service' => ['deploy/systemd/minibib-queue.service'],
            'scheduler service' => ['deploy/systemd/minibib-scheduler.service'],
            'scheduler timer' => ['deploy/systemd/minibib-scheduler.timer'],
        ];
    }

    private function readProjectFile(string $relativePath): string
    {
        $content = file_get_contents(base_path($relativePath));

        $this->assertIsString(
            $content,
            "Die Produktionsdatei {$relativePath} konnte nicht gelesen werden.",
        );

        return $content;
    }
}
