<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ProductionBackupConfigurationTest extends TestCase
{
    public function test_backup_script_creates_verified_atomic_archives(): void
    {
        $content = $this->readProjectFile('deploy/backup/minibib-backup.sh');

        $this->assertStringContainsString('set -Eeuo pipefail', $content);
        $this->assertStringContainsString('umask 0077', $content);
        $this->assertStringContainsString('PGPASSFILE', $content);
        $this->assertStringNotContainsString('PGPASSWORD', $content);
        $this->assertStringContainsString('--format=custom', $content);
        $this->assertStringContainsString('--no-password', $content);
        $this->assertStringContainsString('--no-owner', $content);
        $this->assertStringContainsString('--no-privileges', $content);
        $this->assertStringContainsString('pg_restore', $content);
        $this->assertStringContainsString('--list', $content);
        $this->assertStringContainsString('sha256sum --check SHA256SUMS', $content);
        $this->assertStringContainsString('.partial-$timestamp-$$', $content);
        $this->assertStringContainsString(
            'mv -- "$partial_directory" "$final_directory"',
            $content,
        );
        $this->assertStringContainsString(
            'ln -sfn -- "$timestamp" "$BACKUP_ROOT/latest"',
            $content,
        );
        $this->assertStringContainsString(
            '-mtime +"$RETENTION_DAYS"',
            $content,
        );
    }

    public function test_backup_script_rejects_unsafe_paths_and_credentials(): void
    {
        $content = $this->readProjectFile('deploy/backup/minibib-backup.sh');

        $this->assertStringContainsString('realpath -e "$APP_ROOT"', $content);
        $this->assertStringContainsString('realpath -m "$BACKUP_ROOT"', $content);
        $this->assertStringContainsString(
            '"$BACKUP_ROOT" == "$APP_ROOT/"*',
            $content,
        );
        $this->assertStringContainsString(
            "stat -c '%u' \"\$PGPASSFILE\"",
            $content,
        );
        $this->assertStringContainsString('id -u', $content);
        $this->assertStringContainsString(
            '[[ ! -L "$BACKUP_ROOT/latest" ]]',
            $content,
        );
    }

    public function test_backup_script_excludes_recursive_and_temporary_storage(): void
    {
        $content = $this->readProjectFile('deploy/backup/minibib-backup.sh');

        $this->assertStringContainsString('storage/app/private', $content);
        $this->assertStringContainsString('storage/app/public', $content);
        $this->assertStringContainsString(
            "--exclude='storage/app/backups'",
            $content,
        );
        $this->assertStringContainsString(
            "--exclude='storage/app/private/backups'",
            $content,
        );
        $this->assertStringContainsString(
            "--exclude='storage/app/private/health'",
            $content,
        );
        $this->assertStringContainsString(
            "--exclude='storage/app/private/livewire-tmp'",
            $content,
        );
    }

    public function test_restore_script_uses_a_separate_role_and_safe_destination(): void
    {
        $content = $this->readProjectFile(
            'deploy/backup/minibib-restore-check.sh',
        );

        $this->assertStringContainsString(
            '^minibib_restore_[a-z0-9_]+$',
            $content,
        );
        $this->assertStringContainsString(
            'if [[ "$restore_database" == "$PGDATABASE" ]]',
            $content,
        );
        $this->assertStringContainsString('RESTORE_PGUSER', $content);
        $this->assertStringContainsString('RESTORE_PGPASSFILE', $content);
        $this->assertStringContainsString(
            'if [[ "$RESTORE_PGUSER" == "$PGUSER" ]]',
            $content,
        );
        $this->assertStringContainsString(
            "restore_root='/var/tmp/minibib-restore'",
            $content,
        );
        $this->assertStringContainsString(
            'restore_directory="$(realpath -m "$restore_directory")"',
            $content,
        );
        $this->assertStringContainsString(
            '"$restore_directory" != "$canonical_restore_root/"*',
            $content,
        );
        $this->assertStringNotContainsString('dropdb', $content);
        $this->assertStringContainsString('createdb', $content);
        $this->assertStringContainsString('--maintenance-db=postgres', $content);
        $this->assertStringContainsString(
            'sha256sum --check SHA256SUMS',
            $content,
        );
        $this->assertStringContainsString('--exit-on-error', $content);
        $this->assertStringContainsString('--single-transaction', $content);
        $this->assertStringContainsString('--no-owner', $content);
        $this->assertStringContainsString('--no-privileges', $content);
        $this->assertStringContainsString(
            'minibib_update_media_search_vector()',
            $content,
        );
        $this->assertStringContainsString('--no-same-owner', $content);
        $this->assertStringContainsString('--no-same-permissions', $content);
        $this->assertStringNotContainsString('PGPASSWORD', $content);
    }

    public function test_backup_systemd_units_use_a_hardened_daily_timer(): void
    {
        $service = $this->readProjectFile(
            'deploy/systemd/minibib-backup.service',
        );
        $timer = $this->readProjectFile(
            'deploy/systemd/minibib-backup.timer',
        );

        $this->assertStringContainsString('User=minibib', $service);
        $this->assertStringContainsString('Group=www-data', $service);
        $this->assertStringContainsString(
            'EnvironmentFile=/etc/minibib/backup.env',
            $service,
        );
        $this->assertStringContainsString(
            'ExecStart=/usr/local/sbin/minibib-backup',
            $service,
        );
        $this->assertStringContainsString('UMask=0077', $service);
        $this->assertStringContainsString('NoNewPrivileges=true', $service);
        $this->assertStringContainsString('ProtectSystem=strict', $service);
        $this->assertStringContainsString(
            'ReadWritePaths=/var/backups/minibib',
            $service,
        );
        $this->assertStringNotContainsString('User=root', $service);

        $this->assertStringContainsString(
            'OnCalendar=*-*-* 02:15:00',
            $timer,
        );
        $this->assertStringContainsString('Persistent=true', $timer);
        $this->assertStringContainsString('RandomizedDelaySec=15m', $timer);
        $this->assertStringContainsString(
            'Unit=minibib-backup.service',
            $timer,
        );
    }

    #[DataProvider('productionBackupFileProvider')]
    public function test_backup_templates_do_not_contain_secrets(
        string $relativePath,
    ): void {
        $content = $this->readProjectFile($relativePath);

        $this->assertStringNotContainsString('DB_PASSWORD=', $content);
        $this->assertStringNotContainsString('APP_KEY=', $content);
        $this->assertStringNotContainsString('base64:', $content);
        $this->assertStringNotContainsString('PGPASSWORD=', $content);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function productionBackupFileProvider(): array
    {
        return [
            'backup script' => ['deploy/backup/minibib-backup.sh'],
            'restore script' => ['deploy/backup/minibib-restore-check.sh'],
            'backup environment' => [
                'deploy/backup/minibib-backup.env.example',
            ],
            'restore environment' => [
                'deploy/backup/minibib-restore.env.example',
            ],
            'backup service' => [
                'deploy/systemd/minibib-backup.service',
            ],
            'backup timer' => [
                'deploy/systemd/minibib-backup.timer',
            ],
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
