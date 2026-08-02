<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductionWebServerConfigurationTest extends TestCase
{
    public function test_nginx_serves_only_the_public_directory_through_index_php(): void
    {
        $content = $this->readProjectFile('deploy/nginx/minibib.conf');

        $this->assertStringContainsString(
            'root /srv/minibib/current/public;',
            $content,
        );
        $this->assertStringNotContainsString(
            'root /srv/minibib/current;',
            $content,
        );
        $this->assertStringContainsString(
            'try_files $uri $uri/ /index.php?$query_string;',
            $content,
        );
        $this->assertStringContainsString(
            'location ~ ^/index\.php(/|$)',
            $content,
        );
        $this->assertStringContainsString(
            'location ~ \.php$',
            $content,
        );
        $this->assertStringContainsString('return 404;', $content);
        $this->assertStringContainsString(
            'location ~ ^/storage/.*\.php$',
            $content,
        );
        $this->assertStringContainsString(
            'location ~ /\.(?!well-known).*',
            $content,
        );
    }

    public function test_nginx_uses_https_and_the_minibib_fpm_socket(): void
    {
        $content = $this->readProjectFile('deploy/nginx/minibib.conf');

        $this->assertStringContainsString(
            'return 301 https://$host$request_uri;',
            $content,
        );
        $this->assertStringContainsString('listen 443 ssl;', $content);
        $this->assertStringContainsString(
            'ssl_protocols TLSv1.2 TLSv1.3;',
            $content,
        );
        $this->assertStringContainsString(
            'fastcgi_pass unix:/run/php/minibib.sock;',
            $content,
        );
        $this->assertStringContainsString(
            'fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;',
            $content,
        );
        $this->assertStringContainsString(
            'fastcgi_hide_header X-Powered-By;',
            $content,
        );
        $this->assertStringContainsString(
            'client_max_body_size 25m;',
            $content,
        );
    }

    public function test_php_fpm_pool_runs_without_root_and_matches_nginx(): void
    {
        $content = $this->readProjectFile('deploy/php-fpm/minibib.conf');

        $this->assertStringContainsString('user = minibib', $content);
        $this->assertStringContainsString('group = www-data', $content);
        $this->assertStringNotContainsString('user = root', $content);
        $this->assertStringContainsString(
            'listen = /run/php/minibib.sock',
            $content,
        );
        $this->assertStringContainsString('listen.owner = www-data', $content);
        $this->assertStringContainsString('listen.group = www-data', $content);
        $this->assertStringContainsString('listen.mode = 0660', $content);
        $this->assertStringContainsString('pm = ondemand', $content);
        $this->assertStringContainsString('pm.max_children = 5', $content);
        $this->assertStringContainsString('clear_env = yes', $content);
        $this->assertStringContainsString(
            'security.limit_extensions = .php',
            $content,
        );
        $this->assertStringContainsString(
            'php_admin_flag[display_errors] = off',
            $content,
        );
    }

    public function test_web_server_templates_do_not_contain_secrets(): void
    {
        $content = $this->readProjectFile('deploy/nginx/minibib.conf')
            .$this->readProjectFile('deploy/php-fpm/minibib.conf');

        $this->assertStringNotContainsString('DB_PASSWORD=', $content);
        $this->assertStringNotContainsString('APP_KEY=', $content);
        $this->assertStringNotContainsString('base64:', $content);
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
