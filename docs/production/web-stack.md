# Nginx und PHP-FPM für MiniBib

Diese Vorlagen konfigurieren den Webzugriff auf MiniBib über Nginx und einen
eigenen PHP-8.4-FPM-Pool.

## Dateien

| Repository-Datei | Ziel auf dem Server |
|---|---|
| `deploy/nginx/minibib.conf` | `/etc/nginx/sites-available/minibib` |
| `deploy/php-fpm/minibib.conf` | `/etc/php/8.4/fpm/pool.d/minibib.conf` |

Die Nginx-Konfiguration wird anschließend über
`/etc/nginx/sites-enabled/minibib` aktiviert.

## Festgelegte Serverstruktur

| Bereich | Wert |
|---|---|
| Anwendung | `/srv/minibib/current` |
| öffentlicher Webroot | `/srv/minibib/current/public` |
| Linux-Benutzer | `minibib` |
| Webserver-Gruppe | `www-data` |
| PHP-FPM-Socket | `/run/php/minibib.sock` |
| ACME-Webroot | `/var/www/letsencrypt` |

Nginx darf niemals auf `/srv/minibib/current` zeigen. Nur das Unterverzeichnis
`public` ist als Webroot zulässig. Dadurch bleiben `.env`, Quellcode,
Konfiguration, Datenbankdateien und private Storage-Dateien außerhalb des
öffentlich erreichbaren Verzeichnisbaums.

## Domain und Zertifikat

Die versionierte Vorlage verwendet absichtlich die ungültige Beispieldomain:

```text
bibliothek.example.invalid
```

Vor dem Aktivieren müssen in derselben Nginx-Datei alle drei Vorkommen ersetzt
werden:

1. `server_name` im HTTP-Server
2. `server_name` im HTTPS-Server
3. Pfade zu Zertifikat und privatem Schlüssel

Die Zertifikatsdateien müssen vorhanden sein, bevor die HTTPS-Konfiguration mit
Nginx aktiviert und geprüft wird.

Der HTTP-Server stellt ausschließlich die ACME-Challenge bereit und leitet alle
anderen Anfragen dauerhaft auf HTTPS um.

## Nginx-Schutzmaßnahmen

Die Vorlage:

- verwendet ausschließlich `/srv/minibib/current/public` als Webroot,
- leitet Laravel-Anfragen an `index.php`,
- führt keine anderen PHP-Dateien aus,
- blockiert PHP-Dateien unter einem öffentlichen Storage-Link,
- blockiert versteckte Dateien außerhalb von `.well-known`,
- entfernt den PHP-Header `X-Powered-By`,
- begrenzt Upload-Anfragen auf 25 MiB,
- erlaubt ausschließlich TLS 1.2 und TLS 1.3,
- setzt HSTS sowie grundlegende Browser-Sicherheitsheader.

Die HSTS-Konfiguration darf erst öffentlich aktiviert werden, wenn HTTPS für die
endgültige Domain zuverlässig funktioniert.

## PHP-FPM-Pool

Der Pool läuft als Benutzer `minibib` mit der Gruppe `www-data`. Nginx greift
über den Unix-Socket `/run/php/minibib.sock` zu.

Für die kleine private Anwendung wird `pm = ondemand` verwendet. Prozesse
werden nur bei Bedarf erzeugt. Die Vorlage erlaubt höchstens fünf gleichzeitige
FPM-Prozesse. Dieser Wert muss später anhand des tatsächlich verfügbaren
Arbeitsspeichers und des gemessenen PHP-Speicherverbrauchs geprüft werden.

Die Vorlage setzt außerdem:

- maximal 500 Anfragen pro Workerprozess,
- zehn Sekunden Leerlaufzeit,
- 70 Sekunden harte Laufzeit pro FPM-Anfrage,
- 256 MiB PHP-Speicherlimit,
- 20 MiB maximale Einzeldatei,
- 25 MiB maximale POST-Anfrage,
- deaktivierte Fehleranzeige,
- Fehlerprotokollierung über Syslog,
- ausschließlich `.php` als ausführbare Skripterweiterung.

## Dateirechte

Der Benutzer `minibib` und die Webserver-Gruppe `www-data` benötigen passende
Leserechte für den Anwendungscode.

Laravel benötigt Schreibrechte auf:

```text
/srv/minibib/current/storage
/srv/minibib/current/bootstrap/cache
```

Private Cover liegen unter `storage/app/private` und werden nicht über Nginx
ausgeliefert. Der geschützte Laravel-Controller bleibt der einzige vorgesehene
Zugriffsweg.

## Benötigte Laufzeit

Der Server benötigt PHP 8.4 FPM und die vom Projekt verwendeten Erweiterungen,
insbesondere:

- ctype
- curl
- dom
- fileinfo
- filter
- intl
- mbstring
- openssl
- pcre
- PDO
- pdo_pgsql
- pgsql
- session
- tokenizer
- xml
- xmlwriter
- zip

Die konkrete Paketquelle und Paketbezeichnung werden erst bei der Installation
der gewählten Ubuntu-Version festgelegt.

## Validierung vor dem Neustart

Vor jeder Aktivierung oder Änderung müssen beide Konfigurationen syntaktisch
geprüft werden:

```text
php-fpm8.4 -t
nginx -t
```

Erst nach erfolgreichen Prüfungen werden PHP-FPM und Nginx neu geladen.

## Funktionsprüfung

Nach dem Laden der Konfiguration sind mindestens zu prüfen:

1. HTTP leitet auf HTTPS um.
2. Das Zertifikat gehört zur endgültigen Domain.
3. `/health/live` antwortet mit HTTP 200.
4. `/health/ready` antwortet mit HTTP 200.
5. `/health/ready` meldet Datenbank und Storage als verfügbar.
6. Login und Logout funktionieren.
7. Ein geschütztes Cover ist nach Anmeldung sichtbar.
8. `.env`, `.git` und andere versteckte Dateien sind nicht erreichbar.
9. Eine beliebige PHP-Datei außerhalb von `public/index.php` wird nicht
   ausgeführt.
10. Uploads innerhalb der Anwendungsgrenzen funktionieren.

## Abgrenzung

Diese Vorlagen richten noch keine Ubuntu-Pakete, Firewall, DNS-Einträge oder
Let's-Encrypt-Zertifikate ein. Diese Schritte gehören zur späteren
Serverinstallation und Sicherheitsabnahme.
