# MiniBib

MiniBib ist eine private Bibliotheksverwaltung auf Basis von Laravel, Livewire und PostgreSQL.

## Projektstatus

**Phase 0 – Projektgrundlage: abgeschlossen**

Die aktuelle Grundlage umfasst:

- Laravel 13
- Livewire 4
- PHP 8.4
- PostgreSQL für Entwicklung und Tests
- Vite und Tailwind CSS
- deutsches Gebietsschema und Zeitzone `Europe/Berlin`
- Datenbank-Sessions, Cache und Queue
- Healthchecks für Anwendung und Datenbank
- Queue- und Scheduler-Smoke-Tests
- Laravel Pint
- Larastan/PHPStan
- PHPUnit-Tests
- GitHub Actions CI
- Windows-CMD-Hilfsskripte

## Voraussetzungen

Für die lokale Entwicklung werden benötigt:

- PHP 8.4 mit mindestens:
  - `curl`
  - `dom`
  - `fileinfo`
  - `intl`
  - `mbstring`
  - `openssl`
  - `pdo_pgsql`
  - `pgsql`
  - `xml`
  - `xmlwriter`
  - `zip`
- Composer 2
- Node.js 24 und npm
- PostgreSQL
- Git

## Repository klonen

```cmd
git clone https://github.com/jan-brand/private-bibliothek.git
cd private-bibliothek
```

## PostgreSQL einrichten

Als PostgreSQL-Administrator anmelden:

```cmd
"C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -h 127.0.0.1
```

Rolle und Datenbanken anlegen:

```sql
CREATE ROLE minibib
    WITH LOGIN
    PASSWORD 'DEIN_SICHERES_PASSWORT';

CREATE DATABASE minibib
    WITH OWNER minibib
    ENCODING 'UTF8'
    TEMPLATE template0;

CREATE DATABASE minibib_test
    WITH OWNER minibib
    ENCODING 'UTF8'
    TEMPLATE template0;
```

PostgreSQL verlassen:

```sql
\q
```

## Anwendung konfigurieren

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

In `.env` die PostgreSQL-Verbindung eintragen:

```dotenv
APP_NAME=MiniBib
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=de
APP_FALLBACK_LOCALE=de
APP_FAKER_LOCALE=de_DE
APP_TIMEZONE=Europe/Berlin

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=minibib
DB_USERNAME=minibib
DB_PASSWORD=DEIN_SICHERES_PASSWORT

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=log
```

Datenbank initialisieren:

```cmd
php artisan config:clear
php artisan migrate
php artisan storage:link
```

## Testumgebung konfigurieren

Eine lokale `.env.testing` anlegen:

```dotenv
APP_NAME=MiniBib
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1

APP_LOCALE=de
APP_FALLBACK_LOCALE=de
APP_FAKER_LOCALE=de_DE
APP_TIMEZONE=Europe/Berlin

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=minibib_test
DB_USERNAME=minibib
DB_PASSWORD=DEIN_SICHERES_PASSWORT

CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

Testschlüssel erzeugen und Testdatenbank migrieren:

```cmd
php artisan key:generate --env=testing
php artisan migrate:fresh --env=testing
```

`.env` und `.env.testing` dürfen nicht committed werden.

## Windows-Hilfsskripte

Die vorhandenen CMD-Dateien verwenden auf dem aktuellen Entwicklungsrechner die installierte PHP-8.4-WinGet-Version.

Bei einem anderen Windows-Benutzer oder Installationspfad muss die Variable `PHP84` in den CMD-Dateien angepasst werden.

| Datei | Funktion |
|---|---|
| `dev.cmd` | Laravel-Server, Queue-Worker und Vite starten |
| `artisan.cmd` | Artisan mit PHP 8.4 ausführen |
| `composer84.cmd` | Composer mit PHP 8.4 ausführen |
| `test.cmd` | Tests ausführen |
| `check.cmd` | vollständige Qualitätsprüfung ausführen |
| `format.cmd` | Code mit Pint formatieren |
| `migrate.cmd` | Migrationen ausführen |
| `queue-smoke.cmd` | Queue-Smoke-Test einreihen |
| `scheduler-work.cmd` | Scheduler lokal starten |

## Entwicklungsumgebung starten

```cmd
dev.cmd
```

Alternativ:

```cmd
composer run dev
```

Die Anwendung ist erreichbar unter:

```text
http://127.0.0.1:8000
```

`dev.cmd` startet:

- Laravel-Entwicklungsserver
- Queue-Worker
- Vite-Entwicklungsserver

Der Prozess läuft weiter, bis er mit `Strg + C` beendet wird.

## Healthchecks

Liveness-Check:

```text
GET /health/live
```

Readiness-Check mit Datenbankprüfung:

```text
GET /health/ready
```

Lokal prüfen:

```cmd
curl -i http://127.0.0.1:8000/health/live
curl -i http://127.0.0.1:8000/health/ready
```

Eine erfolgreiche Readiness-Antwort enthält:

```json
{
    "status": "ready",
    "database": "available",
    "checked_at": "..."
}
```

## Tests und Codequalität

Vollständige Prüfung:

```cmd
check.cmd
```

Alternativ:

```cmd
composer check
```

Der Prüfablauf umfasst:

1. Validierung von `composer.json`
2. Laravel Pint im Prüfmodus
3. Larastan/PHPStan
4. PHPUnit
5. Frontend-Produktionsbuild

Einzelne Befehle:

```cmd
test.cmd
format.cmd
composer analyse
npm run build
```

## Queue prüfen

Während `dev.cmd` läuft:

```cmd
queue-smoke.cmd
```

Oder:

```cmd
php artisan minibib:queue-smoke
```

Synchron ohne Queue-Worker:

```cmd
php artisan minibib:queue-smoke --sync
```

Verarbeitete Smoke-Jobs erzeugen JSON-Dateien unter:

```text
storage/app/private/health/queue
```

## Scheduler prüfen

Direkter Smoke-Test:

```cmd
php artisan minibib:scheduler-smoke
```

Registrierte Aufgaben anzeigen:

```cmd
php artisan schedule:list
```

Scheduler lokal ausführen:

```cmd
scheduler-work.cmd
```

Der lokale Smoke-Test läuft jede Minute. In der Produktionsumgebung ist er täglich um `03:05` Uhr registriert.

## GitHub Actions

Der Workflow liegt unter:

```text
.github/workflows/ci.yml
```

Er wird bei Pushes auf `main` und bei Pull Requests ausgeführt. Die CI richtet PHP, Node.js und PostgreSQL ein, installiert die Abhängigkeiten, migriert die Testdatenbank und führt `composer check` aus.

## Sicherheitsregeln

Folgende Dateien und Verzeichnisse dürfen nicht committed werden:

```text
.env
.env.testing
.env*.bak
vendor/
node_modules/
```

Vor einem Commit prüfen:

```cmd
git status
git check-ignore -v .env
git check-ignore -v .env.testing
```

## Nächste Entwicklungsphase

Nach Phase 0 beginnt die fachliche Umsetzung, unter anderem mit:

- Benutzern und privaten beziehungsweise gemeinsamen Bibliotheken
- Medien und physischen Exemplaren
- Standorten bis zur Regalbrett-Ebene
- Listen, Notizen und Ausleihen
- DNB-/ZDB-Import
- Barcode-Erfassung
- Suche, Audit-Log, Export und Backups
