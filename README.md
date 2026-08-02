# MiniBib

MiniBib ist eine private Bibliotheksverwaltung für eine gemeinsame Bibliothek.
Die Anwendung basiert auf Laravel, Livewire und PostgreSQL und unterstützt
private sowie gemeinsam sichtbare Daten.

## Projektstatus

Die fachlichen Phasen 1 bis 8 sind abgeschlossen. Phase 9 zur Produktionsreife
ist in Arbeit.

Der aktuelle Funktionsumfang umfasst:

- Anmeldung ohne öffentliche Registrierung
- Benutzer, Mitgliedschaften und Rollen
- private und gemeinsame Medien
- Exemplare und hierarchische Standorte
- DNB- und ZDB-Metadatenimport
- geschützte private Cover
- ISBN-, Kennungs- und Barcodeverarbeitung
- private und gemeinsame Listen
- persönliche Lesestatus
- entleihende Personen
- Ausleihen, Rückgaben und Überfälligkeit
- PostgreSQL-Volltextsuche und Katalogfilter
- Liveness- und Readiness-Checks
- Queue- und Scheduler-Smoke-Tests

## Technischer Stand

- Laravel 13
- Livewire 4
- PHP 8.4
- PostgreSQL
- Vite und Tailwind CSS
- Locale `de`
- Zeitzone `Europe/Berlin`
- Datenbank-Sessions
- Datenbank-Cache
- Datenbank-Queue
- Laravel Pint
- Larastan/PHPStan
- PHPUnit
- GitHub Actions

## Lokale Voraussetzungen

Erforderlich sind:

- PHP 8.4 mit `curl`, `dom`, `fileinfo`, `intl`, `mbstring`, `openssl`,
  `pdo_pgsql`, `pgsql`, `xml`, `xmlwriter` und `zip`
- Composer 2
- Node.js 24 und npm
- PostgreSQL
- Git

## Lokale Installation

Repository klonen:

```cmd
git clone https://github.com/jan-brand/private-bibliothek.git
cd private-bibliothek
```

Abhängigkeiten installieren:

```cmd
composer install
npm install
```

Lokale Konfiguration erzeugen:

```cmd
copy .env.example .env
php artisan key:generate
```

Die PostgreSQL-Zugangsdaten werden ausschließlich in `.env` eingetragen.

Datenbank initialisieren:

```cmd
php artisan config:clear
php artisan migrate
php artisan storage:link
```

## Testumgebung

Die Testdatenbank wird über `.env.testing` konfiguriert. Diese Datei darf ebenso
wie `.env` nicht committed werden.

Testschlüssel erzeugen und Testdatenbank neu migrieren:

```cmd
php artisan key:generate --env=testing
php artisan migrate:fresh --env=testing
```

## Windows-Hilfsskripte

| Datei | Funktion |
|---|---|
| `dev.cmd` | Entwicklungsserver, Queue-Worker und Vite starten |
| `artisan.cmd` | Artisan mit PHP 8.4 ausführen |
| `composer84.cmd` | Composer mit PHP 8.4 ausführen |
| `test.cmd` | Tests ausführen |
| `check.cmd` | vollständige Qualitätsprüfung ausführen |
| `format.cmd` | Code mit Pint formatieren |
| `migrate.cmd` | Migrationen ausführen |
| `queue-smoke.cmd` | Queue-Smoke-Test einreihen |
| `scheduler-work.cmd` | Scheduler lokal starten |

Die CMD-Dateien verwenden auf dem Entwicklungsrechner den installierten
PHP-8.4-WinGet-Pfad.

## Entwicklungsumgebung

```cmd
dev.cmd
```

Die Anwendung ist anschließend unter `http://127.0.0.1:8000` erreichbar.

## Health-Checks

Liveness:

```text
GET /health/live
```

Readiness:

```text
GET /health/ready
```

Der Readiness-Endpunkt prüft PostgreSQL und den beschreibbaren privaten Storage.
Er ist zustandslos registriert und erzeugt keine Session-Cookies.

Beispiel einer erfolgreichen Antwort:

```json
{
    "status": "ready",
    "database": "available",
    "storage": "writable",
    "checked_at": "2026-08-02T23:13:22+02:00"
}
```

## Qualitätssicherung

Vollständige Prüfung:

```cmd
check.cmd
```

Alternativ:

```cmd
composer check
```

Der Ablauf umfasst:

1. Validierung von `composer.json`
2. Frontend-Produktionsbuild
3. Laravel Pint im Prüfmodus
4. Larastan/PHPStan
5. PHPUnit

## Queue-Smoke-Test

Während der lokale Queue-Worker läuft:

```cmd
queue-smoke.cmd
```

Verarbeitete Jobs erzeugen JSON-Dateien unter:

```text
storage/app/private/health/queue
```

## Scheduler-Smoke-Test

Direkte Ausführung:

```cmd
artisan.cmd minibib:scheduler-smoke
```

Registrierte Aufgaben:

```cmd
artisan.cmd schedule:list
```

Lokaler Scheduler:

```cmd
scheduler-work.cmd
```

## Produktion

Eine sichere Produktionsvorlage liegt in:

```text
.env.production.example
```

Versionierte systemd-Vorlagen liegen in:

```text
deploy/systemd
```

Die zugehörige Betriebsdokumentation liegt in:

```text
docs/production/systemd.md
```

Die Vorlagen verwenden den Linux-Benutzer `minibib`, die Gruppe `www-data` und
den Projektpfad `/srv/minibib/current`.

## GitHub Actions

Der Workflow liegt unter `.github/workflows/ci.yml` und führt bei Pushes auf
`main` sowie bei Pull Requests den vollständigen Projektcheck mit PostgreSQL
aus.

## Sicherheitsregeln

Nicht committen:

```text
.env
.env.testing
.env.production
.env*.bak
vendor
node_modules
```

Produktions-Secrets gehören ausschließlich in die nicht versionierte `.env` auf
dem Server.

## Nächste Produktionsschritte

Phase 9 wird anschließend ergänzt um:

- Nginx und PHP-FPM
- Installations- und Deployment-Ablauf
- PostgreSQL- und Storage-Backups
- Wiederherstellungstest
- HTTPS- und Server-Härtung
- Produktionsabnahme der Health-Checks
