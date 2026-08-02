# Reproduzierbares MiniBib-Deployment

Das Deployment-Skript aktualisiert die produktive Git-Arbeitskopie auf den
exakten aktuellen Commit von `origin/main`, erstellt vorher ein Backup und führt
alle erforderlichen Laravel- und Frontend-Schritte aus.

## Dateien

| Repository-Datei | Ziel auf dem Server |
|---|---|
| `deploy/deployment/minibib-deploy.sh` | `/usr/local/sbin/minibib-deploy` |
| `deploy/deployment/minibib-deploy.env.example` | `/etc/minibib/deploy.env` |

Das Skript erhält Modus `0750`. Die Konfiguration erhält Modus `0640` und darf
nicht gruppen- oder weltbeschreibbar sein.

## Voraussetzungen

Vor dem ersten Deployment müssen vorhanden sein:

- eine saubere Git-Arbeitskopie unter `/srv/minibib/current`,
- der Remote `origin`,
- der Branch `main` auf dem Remote,
- eine nicht versionierte Produktionsdatei `.env`,
- PHP 8.4 CLI,
- Composer 2,
- Node.js 24 und npm,
- `curl`,
- die eingerichteten Backup-Werkzeuge,
- lesbare Git-Zugangsdaten für den Benutzer `minibib`.

Das Skript darf nicht als Root ausgeführt werden. Der Benutzer `minibib` muss
die Arbeitskopie, `storage`, `bootstrap/cache` und die installierten
Abhängigkeiten verwalten können.

## Konfiguration

Die Vorlage verwendet:

```text
APP_ROOT=/srv/minibib/current
DEPLOY_REMOTE=origin
DEPLOY_BRANCH=main
HEALTH_BASE_URL=https://bibliothek.example.invalid
```

Vor dem produktiven Einsatz muss `HEALTH_BASE_URL` auf die endgültige
HTTPS-Adresse geändert werden. Unsichere HTTP-Adressen und URLs mit
abschließendem Schrägstrich werden abgewiesen.

`RUN_BACKUP_BEFORE_DEPLOY=true` ist der sichere Standard. Das Backup läuft vor
dem Wartungsmodus. Schlägt es fehl, wird der Anwendungscode nicht verändert.

## Ablauf

Das Skript führt folgende Schritte aus:

1. Konfiguration, Programme und Projektdateien prüfen.
2. parallele Deployments über `flock` verhindern.
3. eine veränderte oder unversionierte Produktions-Arbeitskopie ablehnen.
4. `origin/main` abrufen.
5. ausschließlich ein Fast-Forward-Ziel akzeptieren.
6. ein vollständiges PostgreSQL- und Storage-Backup erstellen.
7. Laravel mit vorgerenderter 503-Seite in den Wartungsmodus versetzen.
8. exakt den Remote-Commit als detached `HEAD` auschecken.
9. Composer-Pakete ohne Entwicklungsabhängigkeiten installieren.
10. mit `npm ci` exakt den Lockfile-Stand installieren.
11. die Frontend-Assets bauen.
12. Migrationen mit `--force --isolated` ausführen.
13. Laravel-Konfiguration, Routen, Events und Views optimieren.
14. laufende Scheduler-Aufrufe unterbrechen.
15. langlebige Laravel-Dienste neu laden.
16. den Wartungsmodus beenden.
17. Liveness und Readiness über HTTPS prüfen.
18. Datenbank- und Storage-Status im Readiness-JSON validieren.

Der Server bleibt danach absichtlich auf einem detached `HEAD`. Dadurch ist der
ausgeführte Commit eindeutig und die lokale Branch-Referenz wird nicht
unbemerkt verändert.

## Installation

Auf dem Server werden die versionierten Vorlagen installiert:

```text
sudo install -o root -g root -m 0750 deploy/deployment/minibib-deploy.sh /usr/local/sbin/minibib-deploy
sudo install -o root -g minibib -m 0640 deploy/deployment/minibib-deploy.env.example /etc/minibib/deploy.env
```

Anschließend wird `/etc/minibib/deploy.env` geprüft und die Beispieldomain durch
die tatsächliche HTTPS-Adresse ersetzt.

## Ausführung

Das Deployment wird als Anwendungsbenutzer gestartet:

```text
sudo -u minibib /usr/local/sbin/minibib-deploy
```

Die Ausgabe nennt am Ende den exakt bereitgestellten Git-Commit.

## Fehlerverhalten

Fehler vor dem Wartungsmodus verändern die laufende Anwendung nicht.

Fehler nach Aktivierung des Wartungsmodus lassen MiniBib absichtlich im
Wartungsmodus. Dadurch wird kein teilweise aktualisierter Stand öffentlich
ausgeliefert. Nach Diagnose und Korrektur wird das Deployment erneut ausgeführt
oder die Anwendung bewusst manuell aktiviert:

```text
cd /srv/minibib/current
/usr/bin/php artisan up
```

Ein fehlgeschlagener Health-Check tritt erst nach `artisan up` auf. In diesem
Fall ist die Anwendung erreichbar, der Deployment-Prozess endet aber mit einem
Fehlercode und muss untersucht werden.

## Migrationen und Rollback

Datenbankmigrationen werden nicht automatisch zurückgerollt. Ein Code-Rollback
ist nur zulässig, wenn der ältere Code mit dem bereits migrierten Schema
kompatibel ist.

Vor einem manuellen Rollback müssen mindestens geprüft werden:

- das unmittelbar vor dem Deployment erstellte Backup,
- die Migrationsänderungen,
- die Kompatibilität des Ziel-Commits,
- der aktuelle Queue-Inhalt,
- der Zustand von Queue und Scheduler.

Das Skript lehnt Nicht-Fast-Forward-Deployments bewusst ab. Rollbacks sind eine
separate, kontrollierte Betriebsmaßnahme.

## Abnahme nach einem Deployment

Zusätzlich zu den automatischen Health-Checks sind zu prüfen:

1. Login und Logout.
2. Medienkatalog und PostgreSQL-Suche.
3. ein privates Cover.
4. Queue-Smoke-Test.
5. Scheduler-Smoke-Test.
6. Status des Queue-Dienstes.
7. Status des Scheduler-Timers.
8. Nginx- und PHP-FPM-Journal.
9. Backup-Journal und neuestes Backup.
10. `git status` und der bereitgestellte Commit.

## Sicherheitsgrenzen

Das Skript:

- läuft nicht als Root,
- liest keine Secrets aus Argumenten,
- enthält keine Zugangsdaten,
- verwendet kein `git pull`,
- überschreibt keine lokalen Änderungen,
- akzeptiert nur Fast-Forward-Deployments,
- verwendet TLS-validierende Health-Checks,
- nutzt weder `curl --insecure` noch deaktivierte Zertifikatsprüfung,
- hält die Anwendung bei einem teilweise fehlgeschlagenen Deployment offline.
