<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ProductionDeploymentConfigurationTest extends TestCase
{
    public function test_deployment_script_rejects_unsafe_execution(): void
    {
        $content = $this->readProjectFile(
            'deploy/deployment/minibib-deploy.sh',
        );

        $this->assertStringContainsString('set -Eeuo pipefail', $content);
        $this->assertStringContainsString('umask 0027', $content);
        $this->assertStringContainsString(
            'if (( "$(id -u)" == 0 ))',
            $content,
        );
        $this->assertStringContainsString(
            'flock --nonblock 9',
            $content,
        );
        $this->assertStringContainsString(
            'git status --porcelain --untracked-files=all',
            $content,
        );
        $this->assertStringNotContainsString('git pull', $content);
        $this->assertStringNotContainsString('git reset --hard', $content);
    }

    public function test_deployment_uses_an_exact_fast_forward_commit(): void
    {
        $content = $this->readProjectFile(
            'deploy/deployment/minibib-deploy.sh',
        );

        $this->assertStringContainsString(
            'git fetch --prune "$DEPLOY_REMOTE" "$DEPLOY_BRANCH"',
            $content,
        );
        $this->assertStringContainsString(
            'git merge-base --is-ancestor "$current_commit" "$target_commit"',
            $content,
        );
        $this->assertStringContainsString(
            'git checkout --detach --force "$target_commit"',
            $content,
        );
    }

    public function test_backup_runs_before_maintenance_mode(): void
    {
        $content = $this->readProjectFile(
            'deploy/deployment/minibib-deploy.sh',
        );

        $backupPosition = strpos($content, '"$BACKUP_COMMAND"');
        $maintenancePosition = strpos($content, '"$PHP_BINARY" artisan down');

        $this->assertIsInt($backupPosition);
        $this->assertIsInt($maintenancePosition);
        $this->assertLessThan($maintenancePosition, $backupPosition);
    }

    public function test_deployment_uses_locked_dependencies_and_laravel_commands(): void
    {
        $content = $this->readProjectFile(
            'deploy/deployment/minibib-deploy.sh',
        );

        $this->assertStringContainsString(
            'COMPOSER_ALLOW_SUPERUSER=0 "$COMPOSER_BINARY" install',
            $content,
        );
        $this->assertStringContainsString('--no-dev', $content);
        $this->assertStringContainsString('--classmap-authoritative', $content);
        $this->assertStringContainsString('--audit', $content);
        $this->assertStringContainsString('"$NPM_BINARY" ci', $content);
        $this->assertStringContainsString('"$NPM_BINARY" run build', $content);
        $this->assertStringContainsString(
            '"$PHP_BINARY" artisan migrate',
            $content,
        );
        $this->assertStringContainsString('--force', $content);
        $this->assertStringContainsString('--isolated', $content);
        $this->assertStringContainsString(
            '"$PHP_BINARY" artisan optimize --no-interaction',
            $content,
        );
        $this->assertStringContainsString(
            '"$PHP_BINARY" artisan schedule:interrupt --no-interaction',
            $content,
        );
        $this->assertStringContainsString(
            '"$PHP_BINARY" artisan reload --no-interaction',
            $content,
        );
    }

    public function test_failed_partial_deployment_remains_in_maintenance_mode(): void
    {
        $content = $this->readProjectFile(
            'deploy/deployment/minibib-deploy.sh',
        );

        $this->assertStringContainsString('trap report_failure EXIT', $content);
        $this->assertStringContainsString(
            'MiniBib bleibt zum Schutz der Daten im Wartungsmodus.',
            $content,
        );

        $upPosition = strpos(
            $content,
            '"$PHP_BINARY" artisan up --no-interaction',
        );
        $healthPosition = strpos(
            $content,
            '"$HEALTH_BASE_URL/health/live"',
        );

        $this->assertIsInt($upPosition);
        $this->assertIsInt($healthPosition);
        $this->assertLessThan($healthPosition, $upPosition);
    }

    public function test_health_checks_require_https_and_validate_readiness(): void
    {
        $content = $this->readProjectFile(
            'deploy/deployment/minibib-deploy.sh',
        );

        $this->assertStringContainsString(
            '[[ "$HEALTH_BASE_URL" != https://* ]]',
            $content,
        );
        $this->assertStringContainsString(
            '"$HEALTH_BASE_URL/health/live"',
            $content,
        );
        $this->assertStringContainsString(
            '"$HEALTH_BASE_URL/health/ready"',
            $content,
        );
        $this->assertStringContainsString(
            '($data["database"] ?? null) === "available"',
            $content,
        );
        $this->assertStringContainsString(
            '($data["storage"] ?? null) === "writable"',
            $content,
        );
        $this->assertStringNotContainsString('--insecure', $content);
        $this->assertStringNotContainsString('-k ', $content);
    }

    #[DataProvider('deploymentFileProvider')]
    public function test_deployment_templates_do_not_contain_secrets(
        string $relativePath,
    ): void {
        $content = $this->readProjectFile($relativePath);

        $this->assertStringNotContainsString('DB_PASSWORD=', $content);
        $this->assertStringNotContainsString('APP_KEY=', $content);
        $this->assertStringNotContainsString('PGPASSWORD=', $content);
        $this->assertStringNotContainsString('base64:', $content);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function deploymentFileProvider(): array
    {
        return [
            'deployment script' => [
                'deploy/deployment/minibib-deploy.sh',
            ],
            'deployment environment' => [
                'deploy/deployment/minibib-deploy.env.example',
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
