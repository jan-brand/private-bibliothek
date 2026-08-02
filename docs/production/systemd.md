# MiniBib unter systemd

Diese Vorlagen betreiben den Laravel-Queue-Worker und den Laravel-Scheduler auf
einer Ubuntu-VM mit systemd.

## Festgelegte Serverstruktur

Die Vorlagen verwenden bewusst eine einheitliche Zielstruktur:

| Bereich | Wert |
|---|---|
| Linux-Benutzer | `minibib` |
| Webserver-Gruppe | `www-data` |
| Projektpfad | `/srv/minibib/current` |
| PHP-CLI | `/usr/bin/php` |
| Queue-Verbindung | `database` |
| Queue | `default` |
| Protokollierung | systemd-Journal |

Die Produktionsdatei `.env` liegt im Projektwurzelverzeichnis
`/srv/minibib/current`. Sie wird nicht versioniert und muss für den Benutzer
`minibib` lesbar sein.

Die Verzeichnisse `storage` und `bootstrap/cache` müssen für `minibib` und die
Webserver-Gruppe beschreibbar sein.

## Enthaltene Unit-Dateien

### `minibib-queue.service`

Der Dienst startet genau einen Laravel-Queue-Worker:

- Datenbank-Queue `default`
- drei Sekunden Pause bei leerer Queue
- höchstens drei Versuche pro Job
- fünf Sekunden Wartezeit vor einem erneuten Versuch
- Worker-Timeout von 60 Sekunden
- kontrollierter Neustart nach spätestens einer Stunde
- automatischer Neustart nach Fehlern oder geplantem Worker-Ende
- 90 Sekunden Zeit für ein geordnetes Beenden
- Ausführung ohne Root-Rechte

`ExecReload` verwendet `queue:restart`. Laravel legt das Neustartsignal im
konfigurierten Cache ab. MiniBib verwendet dafür den Datenbank-Cache.

### `minibib-scheduler.service`

Der Dienst führt einmal `php artisan schedule:run` aus und beendet sich danach.

### `minibib-scheduler.timer`

Der Timer startet den Scheduler jede Minute. `Persistent=true` sorgt dafür, dass
nach einem ausgeschalteten oder pausierten System ein verpasster Lauf einmal
nachgeholt wird. systemd startet keine zweite Instanz des Oneshot-Dienstes,
solange ein vorheriger Lauf noch aktiv ist.

Für Produktion wird absichtlich nicht `schedule:work` verwendet. Dieser
langlaufende Befehl bleibt der lokalen Entwicklung vorbehalten.

## Installation auf dem Server

Die drei Dateien werden mit unveränderten Namen nach
`/etc/systemd/system` kopiert:

```text
deploy/systemd/minibib-queue.service
deploy/systemd/minibib-scheduler.service
deploy/systemd/minibib-scheduler.timer
```

Danach sind auf dem Server folgende Schritte erforderlich:

1. systemd-Konfiguration neu laden.
2. Queue-Dienst aktivieren und sofort starten.
3. Scheduler-Timer aktivieren und sofort starten.
4. Status beider Komponenten prüfen.
5. Queue-Smoke-Test einreihen.
6. Scheduler-Smoke-Test direkt ausführen.
7. erzeugte Statusdateien unter `storage/app/private/health` prüfen.
8. Journal auf Fehler kontrollieren.

## Prüfungen nach jeder Bereitstellung

Nach einer neuen Anwendungsversion:

1. Migrationen erfolgreich ausführen.
2. Anwendung mit `php artisan optimize` optimieren.
3. langlebige Laravel-Dienste mit `php artisan reload` beenden lassen.
4. prüfen, dass systemd den Queue-Worker neu gestartet hat.
5. `php artisan schedule:interrupt` ausführen, falls künftig Aufgaben unterhalb
   einer Minute registriert werden.
6. `/health/live` und `/health/ready` prüfen.
7. Queue- und Scheduler-Smoke-Tests ausführen.

## Fehlerdiagnose

Bei Queue-Problemen sind mindestens folgende Punkte zu kontrollieren:

- Status von `minibib-queue.service`
- Journal des Queue-Dienstes
- Tabelle `jobs`
- Tabelle `failed_jobs`
- Schreibrechte auf `storage` und `bootstrap/cache`
- Datenbank- und Cache-Verbindung
- Produktionswert `QUEUE_CONNECTION=database`

Bei Scheduler-Problemen:

- Status von `minibib-scheduler.timer`
- letzter und nächster Timerlauf
- Journal von `minibib-scheduler.service`
- Ausgabe von `php artisan schedule:list`
- Zeitzone `Europe/Berlin`
- Produktionsumgebung `APP_ENV=production`

## Sicherheitsgrenzen

Die Unit-Dateien:

- laufen nicht als Root,
- verwenden eine restriktive Dateimaske,
- erhalten keine neuen Privilegien,
- besitzen ein privates temporäres Verzeichnis,
- schreiben Logs in das systemd-Journal,
- enthalten keine Secrets.

Datenbankpasswörter und der Anwendungsschlüssel gehören ausschließlich in die
nicht versionierte Produktionsdatei `.env`.
