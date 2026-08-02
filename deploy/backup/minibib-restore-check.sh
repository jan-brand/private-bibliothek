#!/usr/bin/env bash

set -Eeuo pipefail
umask 0077

if (( $# != 3 )); then
    printf 'Verwendung: %s BACKUP_VERZEICHNIS PRUEFDATENBANK RESTORE_VERZEICHNIS\n' "$0" >&2
    exit 64
fi

backup_directory="$1"
restore_database="$2"
restore_directory="$3"
restore_root='/var/tmp/minibib-restore'

required_variables=(
    PGHOST
    PGPORT
    PGDATABASE
    PGUSER
    RESTORE_PGUSER
    RESTORE_PGPASSFILE
)

for variable in "${required_variables[@]}"; do
    if [[ -z "${!variable:-}" ]]; then
        printf 'Fehlende Umgebungsvariable: %s\n' "$variable" >&2
        exit 64
    fi
done

required_commands=(
    createdb
    find
    id
    pg_restore
    psql
    realpath
    sha256sum
    stat
    tar
)

for command_name in "${required_commands[@]}"; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        printf 'Erforderlicher Befehl nicht gefunden: %s\n' "$command_name" >&2
        exit 69
    fi
done

if [[ ! "$restore_database" =~ ^minibib_restore_[a-z0-9_]+$ ]]; then
    printf 'Die Prüfdatenbank muss mit minibib_restore_ beginnen.\n' >&2
    exit 64
fi

if [[ "$restore_database" == "$PGDATABASE" ]]; then
    printf 'Die Produktionsdatenbank darf nicht als Prüfdatenbank verwendet werden.\n' >&2
    exit 64
fi

if [[ "$RESTORE_PGUSER" == "$PGUSER" ]]; then
    printf 'Für Restore-Prüfungen ist eine getrennte PostgreSQL-Rolle erforderlich.\n' >&2
    exit 64
fi

if [[ -L "$restore_root" ]]; then
    printf 'Das Restore-Stammverzeichnis darf kein symbolischer Link sein.\n' >&2
    exit 73
fi

mkdir -p "$restore_root"

canonical_restore_root="$(realpath -e "$restore_root")"

if [[ "$canonical_restore_root" != "$restore_root" ]]; then
    printf 'Das Restore-Stammverzeichnis muss exakt %s sein.\n' "$restore_root" >&2
    exit 73
fi

restore_directory="$(realpath -m "$restore_directory")"

if [[ "$restore_directory" != "$canonical_restore_root/"* ]]; then
    printf 'Das Restore-Verzeichnis muss direkt unter %s liegen.\n' "$restore_root" >&2
    exit 64
fi

backup_directory="$(realpath -e "$backup_directory")"

required_backup_files=(
    SHA256SUMS
    database.dump
    database.list
    manifest.txt
    storage.tar.gz
)

for backup_file in "${required_backup_files[@]}"; do
    if [[ ! -f "$backup_directory/$backup_file" ]]; then
        printf 'Backup-Datei fehlt: %s\n' "$backup_directory/$backup_file" >&2
        exit 66
    fi
done

if [[ ! -f "$RESTORE_PGPASSFILE" ]] || [[ ! -r "$RESTORE_PGPASSFILE" ]]; then
    printf 'Restore-Passwortdatei ist nicht lesbar: %s\n' "$RESTORE_PGPASSFILE" >&2
    exit 66
fi

if [[ -n "$(find "$RESTORE_PGPASSFILE" -maxdepth 0 -perm /077 -print -quit)" ]]; then
    printf 'Restore-Passwortdatei darf keine Gruppen- oder Weltrechte besitzen.\n' >&2
    exit 77
fi

if [[ "$(stat -c '%u' "$RESTORE_PGPASSFILE")" != "$(id -u)" ]]; then
    printf 'Restore-Passwortdatei muss dem ausführenden Benutzer gehören.\n' >&2
    exit 77
fi

if [[ -e "$restore_directory" ]]; then
    if [[ ! -d "$restore_directory" ]] || [[ -L "$restore_directory" ]] || [[ -n "$(find "$restore_directory" -mindepth 1 -print -quit)" ]]; then
        printf 'Restore-Verzeichnis muss leer, echt und nicht symbolisch sein.\n' >&2
        exit 73
    fi
else
    mkdir "$restore_directory"
fi

application_database="$PGDATABASE"
application_user="$PGUSER"

export PGHOST
export PGPORT
export PGUSER="$RESTORE_PGUSER"
export PGPASSFILE="$RESTORE_PGPASSFILE"
unset PGDATABASE

(
    cd "$backup_directory"
    sha256sum --check SHA256SUMS
)

pg_restore \
    --list \
    "$backup_directory/database.dump" \
    >/dev/null

createdb \
    --no-password \
    --maintenance-db=postgres \
    --template=template0 \
    --encoding=UTF8 \
    "$restore_database"

pg_restore \
    --exit-on-error \
    --single-transaction \
    --no-owner \
    --no-privileges \
    --dbname="$restore_database" \
    "$backup_directory/database.dump"

schema_ready="$(
    psql \
        --no-psqlrc \
        --no-password \
        --tuples-only \
        --no-align \
        --set=ON_ERROR_STOP=1 \
        --dbname="$restore_database" \
        --command="
            SELECT (
                to_regclass('public.migrations') IS NOT NULL
                AND to_regclass('public.media') IS NOT NULL
                AND to_regclass('public.copies') IS NOT NULL
                AND to_regclass('public.loans') IS NOT NULL
                AND to_regprocedure(
                    'public.minibib_update_media_search_vector()'
                ) IS NOT NULL
            )::integer;
        "
)"

if [[ "$schema_ready" != "1" ]]; then
    printf 'Die wiederhergestellte Datenbank enthält nicht alle erwarteten Objekte.\n' >&2
    exit 65
fi

tar \
    --extract \
    --gzip \
    --no-same-owner \
    --no-same-permissions \
    --file="$backup_directory/storage.tar.gz" \
    --directory="$restore_directory"

if [[ ! -d "$restore_directory/storage/app/private" ]]; then
    printf 'Privater Storage wurde nicht korrekt wiederhergestellt.\n' >&2
    exit 65
fi

printf 'Restore-Prüfung erfolgreich.\n'
printf 'Produktionsdatenbank blieb unangetastet: %s\n' "$application_database"
printf 'Anwendungsrolle wurde nicht verwendet: %s\n' "$application_user"
printf 'Prüfdatenbank: %s\n' "$restore_database"
printf 'Restore-Verzeichnis: %s\n' "$restore_directory"
printf 'Die Prüfdatenbank und das Restore-Verzeichnis bleiben zur manuellen Abnahme erhalten.\n'
