# MiniBib – Phase 0 ohne Docker

Dieses Paket richtet ein natives Laravel-13-Projekt für die Mini-Bibliothekssoftware ein.
Es verwendet keine Container. Die lokale Bedienung erfolgt unter Windows über `cmd.exe`.

## Voraussetzungen

- Windows 10 oder 11
- PHP 8.3 oder neuer, empfohlen PHP 8.5
- Composer 2
- Node.js 22 LTS oder neuer mit npm
- PostgreSQL 18 oder eine andere unterstützte PostgreSQL-Version
- Git für Windows

Die PHP-Erweiterungen `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `curl`, `fileinfo`,
`xml`, `ctype`, `tokenizer`, `zip`, `intl` und `gd` oder `imagick` sollten aktiviert sein.

## Schnellstart

Öffne die Windows-Eingabeaufforderung im entpackten Projektordner:

```cmd
preflight.cmd
init.cmd
database-create.cmd
migrate.cmd
dev.cmd
```

Danach:

- Anwendung: http://127.0.0.1:8000
- Vite: wird in einem eigenen CMD-Fenster ausgeführt
- Queue: wird in einem eigenen CMD-Fenster ausgeführt
- Scheduler: wird in einem eigenen CMD-Fenster ausgeführt

`init.cmd` lädt mit Composer das offizielle Laravel-13-Grundprojekt, kopiert es in den
aktuellen Ordner und ergänzt anschließend den MiniBib-Code aus `phase0\overlay`.

## Wichtige Befehle

```cmd
help.cmd
preflight.cmd
init.cmd
database-create.cmd
migrate.cmd
dev.cmd
stop-dev.cmd
test.cmd
check.cmd
format.cmd
artisan.cmd about
```

## Inhalt dieser Phase

- native Laravel-Grundinstallation
- PostgreSQL-Konfiguration
- CMD-Skripte für Entwicklung und Prüfung
- responsive MiniBib-Startseite
- Live- und Readiness-Endpunkte
- Queue- und Scheduler-Testmechanismus
- Diagnosebefehl `minibib:doctor`
- Feature-Tests
- GitHub-Actions-CI
- Vorlagen für Nginx, PHP-FPM, systemd und Deployment auf Ubuntu

## Noch nicht enthalten

Phase 0 enthält noch keine Anmeldung, Bibliothekstabellen oder Medienverwaltung.
Diese Funktionen beginnen mit Phase 1.

Weitere Anleitungen:

- `docs\WINDOWS-CMD-INSTALLATION.md`
- `phase0\ubuntu\README.md`
