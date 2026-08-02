#!/usr/bin/env bash

set -Eeuo pipefail
umask 0027

config_file="${MINIBIB_DEPLOY_CONFIG:-/etc/minibib/deploy.env}"

if [[ ! -f "$config_file" ]] || [[ ! -r "$config_file" ]]; then
    printf 'Deployment-Konfiguration ist nicht lesbar: %s\n' "$config_file" >&2
    exit 66
fi

if [[ -n "$(find "$config_file" -maxdepth 0 -perm /022 -print -quit)" ]]; then
    printf 'Deployment-Konfiguration darf nicht gruppen- oder weltbeschreibbar sein.\n' >&2
    exit 77
fi

set -a
# shellcheck disable=SC1090
source "$config_file"
set +a

required_variables=(
    APP_ROOT
    DEPLOY_REMOTE
    DEPLOY_BRANCH
    HEALTH_BASE_URL
    HEALTH_TIMEOUT_SECONDS
    PHP_BINARY
    COMPOSER_BINARY
    NPM_BINARY
    CURL_BINARY
    RUN_BACKUP_BEFORE_DEPLOY
    BACKUP_COMMAND
    BACKUP_ENV_FILE
)

for variable in "${required_variables[@]}"; do
    if [[ -z "${!variable:-}" ]]; then
        printf 'Fehlende Umgebungsvariable: %s\n' "$variable" >&2
        exit 64
    fi
done

if (( "$(id -u)" == 0 )); then
    printf 'Das Deployment darf nicht als Root ausgeführt werden.\n' >&2
    exit 77
fi

required_commands=(
    find
    flock
    git
    id
    realpath
)

for command_name in "${required_commands[@]}"; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        printf 'Erforderlicher Befehl nicht gefunden: %s\n' "$command_name" >&2
        exit 69
    fi
done

for executable in \
    "$PHP_BINARY" \
    "$COMPOSER_BINARY" \
    "$NPM_BINARY" \
    "$CURL_BINARY" \
    "$BACKUP_COMMAND"; do
    if [[ ! -x "$executable" ]]; then
        printf 'Programm ist nicht ausführbar: %s\n' "$executable" >&2
        exit 69
    fi
done

if [[ ! "$DEPLOY_REMOTE" =~ ^[A-Za-z0-9._-]+$ ]]; then
    printf 'DEPLOY_REMOTE enthält unzulässige Zeichen.\n' >&2
    exit 64
fi

if [[ ! "$DEPLOY_BRANCH" =~ ^[A-Za-z0-9._/-]+$ ]] \
    || [[ "$DEPLOY_BRANCH" == -* ]] \
    || [[ "$DEPLOY_BRANCH" == */../* ]] \
    || [[ "$DEPLOY_BRANCH" == ../* ]] \
    || [[ "$DEPLOY_BRANCH" == */.. ]]; then
    printf 'DEPLOY_BRANCH ist ungültig.\n' >&2
    exit 64
fi

if [[ "$HEALTH_BASE_URL" != https://* ]] \
    || [[ "$HEALTH_BASE_URL" == */ ]]; then
    printf 'HEALTH_BASE_URL muss eine HTTPS-URL ohne abschließenden Schrägstrich sein.\n' >&2
    exit 64
fi

if [[ ! "$HEALTH_TIMEOUT_SECONDS" =~ ^[0-9]+$ ]] \
    || (( HEALTH_TIMEOUT_SECONDS < 1 || HEALTH_TIMEOUT_SECONDS > 120 )); then
    printf 'HEALTH_TIMEOUT_SECONDS muss zwischen 1 und 120 liegen.\n' >&2
    exit 64
fi

if [[ "$RUN_BACKUP_BEFORE_DEPLOY" != true ]] \
    && [[ "$RUN_BACKUP_BEFORE_DEPLOY" != false ]]; then
    printf 'RUN_BACKUP_BEFORE_DEPLOY muss true oder false sein.\n' >&2
    exit 64
fi

APP_ROOT="$(realpath -e "$APP_ROOT")"

if [[ ! -d "$APP_ROOT/.git" ]]; then
    printf 'Git-Arbeitskopie nicht gefunden: %s\n' "$APP_ROOT" >&2
    exit 66
fi

required_project_files=(
    .env
    artisan
    composer.json
    composer.lock
    package.json
    package-lock.json
)

for project_file in "${required_project_files[@]}"; do
    if [[ ! -f "$APP_ROOT/$project_file" ]]; then
        printf 'Erforderliche Projektdatei fehlt: %s\n' "$APP_ROOT/$project_file" >&2
        exit 66
    fi
done

if [[ ! -d "$APP_ROOT/storage/framework" ]]; then
    printf 'Laravel-Framework-Storage fehlt: %s/storage/framework\n' "$APP_ROOT" >&2
    exit 66
fi

cd "$APP_ROOT"

exec 9>"$APP_ROOT/storage/framework/.deploy.lock"

if ! flock --nonblock 9; then
    printf 'Ein MiniBib-Deployment läuft bereits.\n' >&2
    exit 75
fi

if [[ -n "$(git status --porcelain --untracked-files=all)" ]]; then
    printf 'Die Produktions-Arbeitskopie enthält nicht gespeicherte Änderungen.\n' >&2
    exit 65
fi

current_commit="$(git rev-parse --verify HEAD)"

git fetch --prune "$DEPLOY_REMOTE" "$DEPLOY_BRANCH"

target_ref="$DEPLOY_REMOTE/$DEPLOY_BRANCH"
target_commit="$(git rev-parse --verify "${target_ref}^{commit}")"

if [[ "$current_commit" == "$target_commit" ]]; then
    printf 'MiniBib ist bereits auf Commit %s.\n' "$target_commit"
    exit 0
fi

if ! git merge-base --is-ancestor "$current_commit" "$target_commit"; then
    printf 'Das Ziel ist kein Fast-Forward von %s auf %s.\n' \
        "$current_commit" \
        "$target_commit" >&2
    exit 65
fi

if [[ "$RUN_BACKUP_BEFORE_DEPLOY" == true ]]; then
    if [[ ! -f "$BACKUP_ENV_FILE" ]] || [[ ! -r "$BACKUP_ENV_FILE" ]]; then
        printf 'Backup-Konfiguration ist nicht lesbar: %s\n' \
            "$BACKUP_ENV_FILE" >&2
        exit 66
    fi

    (
        set -a
        # shellcheck disable=SC1090
        source "$BACKUP_ENV_FILE"
        set +a

        "$BACKUP_COMMAND"
    )
fi

maintenance_enabled=false

report_failure() {
    status=$?

    if (( status == 0 )); then
        return
    fi

    printf 'Deployment fehlgeschlagen (Exit-Code %s).\n' "$status" >&2

    if [[ "$maintenance_enabled" == true ]]; then
        printf 'MiniBib bleibt zum Schutz der Daten im Wartungsmodus.\n' >&2
        printf 'Nach der Fehlerbehebung manuell ausführen: %s artisan up\n' \
            "$PHP_BINARY" >&2
    fi
}

trap report_failure EXIT

"$PHP_BINARY" artisan down \
    --render=errors::503 \
    --retry=60 \
    --refresh=15 \
    --no-interaction

maintenance_enabled=true

git checkout --detach --force "$target_commit"

COMPOSER_ALLOW_SUPERUSER=0 "$COMPOSER_BINARY" install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --classmap-authoritative \
    --audit

"$NPM_BINARY" ci \
    --no-audit \
    --no-fund

"$NPM_BINARY" run build

"$PHP_BINARY" artisan migrate \
    --force \
    --isolated \
    --no-interaction

"$PHP_BINARY" artisan optimize --no-interaction
"$PHP_BINARY" artisan schedule:interrupt --no-interaction
"$PHP_BINARY" artisan reload --no-interaction
"$PHP_BINARY" artisan up --no-interaction

maintenance_enabled=false

curl_options=(
    --fail
    --silent
    --show-error
    --location
    --max-time "$HEALTH_TIMEOUT_SECONDS"
    --retry 5
    --retry-delay 2
    --retry-connrefused
)

"$CURL_BINARY" \
    "${curl_options[@]}" \
    "$HEALTH_BASE_URL/health/live" \
    >/dev/null

ready_response="$(
    "$CURL_BINARY" \
        "${curl_options[@]}" \
        "$HEALTH_BASE_URL/health/ready"
)"

printf '%s' "$ready_response" | "$PHP_BINARY" -r '
$data = json_decode(
    stream_get_contents(STDIN),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

$ready = ($data["status"] ?? null) === "ready"
    && ($data["database"] ?? null) === "available"
    && ($data["storage"] ?? null) === "writable";

if (! $ready) {
    fwrite(STDERR, "Readiness-Antwort ist nicht vollständig bereit.\n");
    exit(1);
}
'

printf 'MiniBib erfolgreich auf Commit %s bereitgestellt.\n' "$target_commit"
