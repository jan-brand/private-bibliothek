# Backup und Wiederherstellung

MiniBib sichert die PostgreSQL-Datenbank und die relevanten Laravel-Storage-Daten
in einem gemeinsamen, zeitgestempelten Backup-Verzeichnis.

## Sicherungsziel

Backups liegen außerhalb des Webroots unter:

```text
/var/backups/minibib
```

Ein vollständiges Backup besitzt beispielsweise folgende Struktur:

```text
/var/backups/minibib/20260803T001500Z/
├── SHA256SUMS
├── database.dump
├── database.list
├── manifest.txt
└── storage.tar.gz
```

Der symbolische Link `/var/backups/minibib/latest` zeigt auf das zuletzt
erfolgreich abgeschlossene Backup.

Unvollständige Sicherungen werden zunächst als verstecktes
`.partial-...`-Verzeichnis geschrieben. Erst nachdem Datenbankarchiv,
Storage-Archiv und Prüfsummen erfolgreich validiert wurden, wird das Verzeichnis
atomar auf seinen endgültigen Namen verschoben.

## Datenbankformat

`database.dump` ist ein PostgreSQL-Custom-Archiv. Dieses Format:

- ist standardmäßig komprimiert,
- wird mit `pg_restore` wiederhergestellt,
- erlaubt die Prüfung des Inhaltsverzeichnisses,
- ist zwischen unterstützten Plattformen portabel.

Das Backup wird ohne Eigentümer- und ACL-Anweisungen erstellt. Bei der
Wiederherstellung gehören die Datenbankobjekte deshalb der Rolle, welche
`pg_restore` ausführt. Die PostgreSQL-Rolle `minibib` muss auf einem neuen
System vor dem Restore separat angelegt werden.

## Gesicherter Storage

Gesichert werden, sofern vorhanden:

```text
storage/app/private
storage/app/public
```

Ausgeschlossen werden bewusst:

```text
storage/app/backups
storage/app/private/backups
storage/app/private/health
storage/app/private/livewire-tmp
```

Damit werden keine Backups rekursiv in neue Backups aufgenommen. Health- und
Queue-Smoke-Dateien sowie temporäre Livewire-Uploads sind keine dauerhaften
Nutzdaten.

Private Cover unter `storage/app/private/covers` sind Bestandteil des Archivs.

## Passwortübergabe

Die Skripte verwenden ausschließlich die von PostgreSQL unterstützte
Passwortdatei:

```text
/etc/minibib/pgpass
```

Der Pfad wird über `PGPASSFILE` gesetzt. Das Passwort erscheint weder in der
Kommandozeile noch in der versionierten Umgebungsdatei.

Die Datei muss dem Benutzer `minibib` gehören und darf keine Gruppen- oder
Weltrechte besitzen. Empfohlen ist Modus `0600`. PostgreSQL ignoriert unter
Unix Passwortdateien mit zu offenen Rechten.

Für das tägliche Backup wird nur der Zugriff auf die Produktionsdatenbank
benötigt. Die Datei `/etc/minibib/pgpass` enthält deshalb eine Zeile nach diesem
Muster:

```text
127.0.0.1:5432:minibib:minibib:<nicht versioniertes Passwort>
```

Der tatsächliche Passwortwert wird ausschließlich aus dem sicheren
Produktionszugang übernommen und niemals in das Repository geschrieben.

## Getrennte Rolle für Restore-Prüfungen

Die Anwendungsrolle `minibib` besitzt absichtlich keine Berechtigung zum Anlegen
von Datenbanken. Restore-Prüfungen verwenden deshalb eine eigenständige
PostgreSQL-Rolle, beispielsweise `minibib_restore_operator`.

Diese Rolle benötigt:

- `LOGIN`,
- `CREATEDB`,
- keine Superuser-Rechte,
- keine Verwendung durch die laufende Anwendung.

Die Restore-Konfiguration liegt getrennt unter `/etc/minibib/restore.env`.
Da die Prüfdatenbank bei jedem Lauf einen neuen Namen erhält und `createdb` eine
Verbindung zur Wartungsdatenbank `postgres` benötigt, verwendet
`/etc/minibib/pgpass-restore` ein Datenbank-Platzhalterfeld:

```text
127.0.0.1:5432:*:minibib_restore_operator:<nicht versioniertes Passwort>
```

Restore-Zugangsdaten dürfen nicht in der Backup-Unit, der Anwendungsdatei `.env`
oder im Repository stehen.

## Installation

Folgende Repository-Dateien werden auf dem Server installiert:

| Quelle | Ziel |
|---|---|
| `deploy/backup/minibib-backup.sh` | `/usr/local/sbin/minibib-backup` |
| `deploy/backup/minibib-restore-check.sh` | `/usr/local/sbin/minibib-restore-check` |
| `deploy/backup/minibib-backup.env.example` | `/etc/minibib/backup.env` |
| `deploy/backup/minibib-restore.env.example` | `/etc/minibib/restore.env` |
| `deploy/systemd/minibib-backup.service` | `/etc/systemd/system/minibib-backup.service` |
| `deploy/systemd/minibib-backup.timer` | `/etc/systemd/system/minibib-backup.timer` |

Die beiden Skripte erhalten Modus `0750`. Die beiden Umgebungsdateien erhalten
Modus `0640`. Beide PostgreSQL-Passwortdateien erhalten Modus `0600` und müssen
dem jeweils ausführenden Betriebssystembenutzer gehören.

Das Backup-Verzeichnis wird vor dem ersten Lauf angelegt und dem Benutzer
`minibib` zugewiesen. Es darf nicht unterhalb von `/srv/minibib/current/public`
oder einem anderen Webroot liegen.

## Zeitplan und Aufbewahrung

Der systemd-Timer startet täglich um `02:15` Uhr und darf den Start um höchstens
15 Minuten zufällig verzögern. `Persistent=true` holt einen verpassten Lauf nach,
wenn die VM zum geplanten Zeitpunkt ausgeschaltet war.

Die Beispielkonfiguration bewahrt 14 Tage auf:

```text
RETENTION_DAYS=14
```

Nur vollständig abgeschlossene, zeitgestempelte Backup-Verzeichnisse werden von
der Aufbewahrungsbereinigung berücksichtigt.

## Manuelle Backup-Prüfung

Vor Aktivierung des Timers wird ein Backup manuell gestartet. Danach sind zu
prüfen:

1. systemd-Dienst endet erfolgreich,
2. ein neues Zeitstempelverzeichnis ist vorhanden,
3. `latest` zeigt auf dieses Verzeichnis,
4. `sha256sum --check SHA256SUMS` ist erfolgreich,
5. `pg_restore --list database.dump` ist erfolgreich,
6. `tar --list --gzip --file=storage.tar.gz` ist erfolgreich,
7. das Journal enthält keine Warnungen oder Fehler,
8. keine `.partial-...`-Verzeichnisse bleiben zurück.

## Automatischer Restore-Check

Das Skript `minibib-restore-check` stellt ein Backup ausschließlich in eine
separate Prüfdatenbank und ein separates Verzeichnis wieder her.

Zulässige Prüfdatenbanken beginnen zwingend mit:

```text
minibib_restore_
```

Zulässige Restore-Verzeichnisse liegen zwingend unter:

```text
/var/tmp/minibib-restore/
```

Das Skript:

1. prüft alle SHA-256-Prüfsummen,
2. prüft das PostgreSQL-Archiv,
3. verweigert die normale Anwendungsrolle,
4. legt ausschließlich eine noch nicht vorhandene Prüfdatenbank an,
5. stellt das Archiv in einer Transaktion wieder her,
6. prüft zentrale Tabellen und die Volltextsuchfunktion,
7. kanonisiert und begrenzt den Restore-Pfad auf `/var/tmp/minibib-restore`,
8. extrahiert den Storage ohne Übernahme fremder Eigentümer oder Rechte,
9. belässt Datenbank und Dateien für die manuelle Abnahme.

Die Produktionsdatenbank wird ausdrücklich abgewiesen. Eine bereits vorhandene
Prüfdatenbank wird nicht gelöscht oder überschrieben; der Lauf bricht stattdessen
ab.

Vor dem manuellen Aufruf werden die Variablen aus
`/etc/minibib/restore.env` exportiert. Das Restore-Skript wird von einem
Administrationskonto ausgeführt, dem auch `/etc/minibib/pgpass-restore` gehört.

## Vollständiger Restore-Test

Ein vertrauenswürdiger Restore-Test umfasst zusätzlich zur Skriptprüfung:

1. eine leere Prüfdatenbank mit eigenständigem Namen,
2. ein leeres Restore-Verzeichnis,
3. eine isolierte Anwendungskopie außerhalb des Produktionspfads,
4. eine eigene nicht versionierte `.env` für die Prüfdatenbank,
5. `php artisan migrate:status`,
6. Anmeldung mit einem wiederhergestellten Konto,
7. Öffnen gemeinsamer und privater Medien,
8. Prüfung geschützter privater Cover,
9. Prüfung der Ausleihhistorie,
10. Volltextsuche nach Titel und Kennung,
11. `/health/live`,
12. `/health/ready`,
13. Kontrolle, dass das Readiness-Ergebnis den Restore-Storage prüft,
14. dokumentierte Löschung der Prüfdatenbank und der Prüfdaten nach Abnahme.

Ein Backup gilt erst nach einem vollständig dokumentierten Restore-Test als
betriebsbereit.

## Externe Kopie

Ein lokales Backup auf derselben VM schützt nicht vor vollständigem
Serververlust. Nach erfolgreichem Abschluss muss das gesamte
Zeitstempelverzeichnis zusätzlich verschlüsselt auf ein getrenntes Ziel
übertragen werden.

Die konkrete Offsite-Technik und das Zielsystem werden erst bei der
Serverinstallation festgelegt. Zugangsdaten für das externe Ziel dürfen weder im
Repository noch in systemd-Unit-Dateien stehen.

## Überwachung

Zu überwachen sind mindestens:

- Ergebnis von `minibib-backup.service`,
- Alter des Ziels von `latest`,
- freier Speicher unter `/var/backups/minibib`,
- verbliebene `.partial-...`-Verzeichnisse,
- Fehler im systemd-Journal,
- regelmäßige Restore-Tests.

Ein erfolgreich gestarteter Timer allein beweist noch kein erfolgreiches
Backup.
