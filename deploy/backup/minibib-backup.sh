#!/usr/bin/env bash

set -Eeuo pipefail
umask 0077

required_variables=(
    APP_ROOT
    BACKUP_ROOT
    RETENTION_DAYS
    PGHOST
    PGPORT
    PGDATABASE
    PGUSER
    PGPASSFILE
)

for variable in "${required_variables[@]}"; do
    if [[ -z "${!variable:-}" ]]; then
        printf 'Fehlende Umgebungsvariable: %s\n' "$variable" >&2
        exit 64
    fi
done

required_commands=(
    date
    find
    flock
    id
    pg_dump
    pg_restore
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

if [[ ! "$RETENTION_DAYS" =~ ^[0-9]+$ ]] || (( RETENTION_DAYS < 1 )); then
    printf 'RETENTION_DAYS muss eine positive ganze Zahl sein.\n' >&2
    exit 64
fi

APP_ROOT="$(realpath -e "$APP_ROOT")"
BACKUP_ROOT="$(realpath -m "$BACKUP_ROOT")"

if [[ "$BACKUP_ROOT" != /* ]] || [[ "$BACKUP_ROOT" == / ]]; then
    printf 'BACKUP_ROOT muss ein absolutes Verzeichnis unterhalb der Wurzel sein.\n' >&2
    exit 64
fi

if [[ "$BACKUP_ROOT" == "$APP_ROOT" ]] || [[ "$BACKUP_ROOT" == "$APP_ROOT/"* ]]; then
    printf 'BACKUP_ROOT darf nicht innerhalb des Anwendungsverzeichnisses liegen.\n' >&2
    exit 64
fi

if [[ ! -d "$APP_ROOT/storage/app" ]]; then
    printf 'Laravel-Storage nicht gefunden: %s/storage/app\n' "$APP_ROOT" >&2
    exit 66
fi

if [[ ! -f "$PGPASSFILE" ]] || [[ ! -r "$PGPASSFILE" ]]; then
    printf 'PostgreSQL-Passwortdatei ist nicht lesbar: %s\n' "$PGPASSFILE" >&2
    exit 66
fi

if [[ -n "$(find "$PGPASSFILE" -maxdepth 0 -perm /077 -print -quit)" ]]; then
    printf 'PostgreSQL-Passwortdatei darf keine Gruppen- oder Weltrechte besitzen.\n' >&2
    exit 77
fi

if [[ "$(stat -c '%u' "$PGPASSFILE")" != "$(id -u)" ]]; then
    printf 'PostgreSQL-Passwortdatei muss dem ausführenden Benutzer gehören.\n' >&2
    exit 77
fi

mkdir -p "$BACKUP_ROOT"

if [[ -e "$BACKUP_ROOT/latest" ]] && [[ ! -L "$BACKUP_ROOT/latest" ]]; then
    printf 'Der Pfad %s/latest ist kein symbolischer Link.\n' "$BACKUP_ROOT" >&2
    exit 73
fi

exec 9>"$BACKUP_ROOT/.backup.lock"

if ! flock --nonblock 9; then
    printf 'Ein MiniBib-Backup läuft bereits.\n' >&2
    exit 75
fi

timestamp="$(date -u +'%Y%m%dT%H%M%SZ')"
partial_directory="$BACKUP_ROOT/.partial-$timestamp-$$"
final_directory="$BACKUP_ROOT/$timestamp"

if [[ -e "$final_directory" ]]; then
    printf 'Backup-Ziel existiert bereits: %s\n' "$final_directory" >&2
    exit 73
fi

mkdir "$partial_directory"

cleanup_partial_backup() {
    rm -rf -- "$partial_directory"
}

trap cleanup_partial_backup EXIT

export PGHOST
export PGPORT
export PGDATABASE
export PGUSER
export PGPASSFILE

pg_dump \
    --format=custom \
    --no-owner \
    --no-privileges \
    --no-password \
    --file="$partial_directory/database.dump"

pg_restore \
    --list \
    "$partial_directory/database.dump" \
    > "$partial_directory/database.list"

storage_paths=()

if [[ -d "$APP_ROOT/storage/app/private" ]]; then
    storage_paths+=("storage/app/private")
fi

if [[ -d "$APP_ROOT/storage/app/public" ]]; then
    storage_paths+=("storage/app/public")
fi

if (( ${#storage_paths[@]} == 0 )); then
    printf 'Keine zu sichernden Storage-Verzeichnisse gefunden.\n' >&2
    exit 66
fi

tar \
    --create \
    --gzip \
    --file="$partial_directory/storage.tar.gz" \
    --directory="$APP_ROOT" \
    --exclude='storage/app/backups' \
    --exclude='storage/app/private/backups' \
    --exclude='storage/app/private/health' \
    --exclude='storage/app/private/livewire-tmp' \
    "${storage_paths[@]}"

application_commit="$(
    git -C "$APP_ROOT" rev-parse HEAD 2>/dev/null || printf 'unknown'
)"

{
    printf 'created_at_utc=%s\n' "$timestamp"
    printf 'application_commit=%s\n' "$application_commit"
    printf 'database=%s\n' "$PGDATABASE"
    printf 'database_format=postgresql-custom\n'
    printf 'storage_format=tar-gzip\n'
    printf 'retention_days=%s\n' "$RETENTION_DAYS"
    printf 'pg_dump_version=%s\n' "$(pg_dump --version)"
} > "$partial_directory/manifest.txt"

(
    cd "$partial_directory"
    sha256sum \
        database.dump \
        database.list \
        storage.tar.gz \
        manifest.txt \
        > SHA256SUMS
    sha256sum --check SHA256SUMS
)

tar \
    --list \
    --gzip \
    --file="$partial_directory/storage.tar.gz" \
    >/dev/null

mv -- "$partial_directory" "$final_directory"
trap - EXIT

ln -sfn -- "$timestamp" "$BACKUP_ROOT/latest"

find "$BACKUP_ROOT" \
    -mindepth 1 \
    -maxdepth 1 \
    -type d \
    -name '20????????T??????Z' \
    -mtime +"$RETENTION_DAYS" \
    -exec rm -rf -- {} +

printf 'MiniBib-Backup erfolgreich erstellt: %s\n' "$final_directory"
