#!/usr/bin/env bash
set -euo pipefail
umask 077

cd -- "$(dirname -- "$0")/.."
source scripts/seed-common.sh

seed_dir=$(seed_directory "$@")
running=$(docker compose ps --status running --services ojs)
if [[ "$running" != ojs ]]; then
    printf '%s\n' 'Start the configured test journal before capturing a seed.' >&2
    exit 1
fi

# Publish only a matched pair after both exports have succeeded.
mkdir -p -- "$seed_dir"
staging=$(mktemp -d "$seed_dir/.capture.XXXXXXXX")
stopped=0
cleanup() {
    local result=$?
    rm -rf -- "$staging"
    if (( stopped )); then
        docker compose start ojs || result=1
    fi
    exit "$result"
}
trap cleanup EXIT

# Capture the journal while OJS is stopped so the database and files match.
docker compose stop ojs
stopped=1
counts=$(docker compose exec -T db sh -ec '
    export MYSQL_PWD="$MARIADB_PASSWORD"
    exec mariadb --user="$MARIADB_USER" --database="$MARIADB_DATABASE" \
        --batch --skip-column-names --execute="
            SELECT COUNT(*) FROM journals WHERE path = '\''vestnik-it'\'';
            SELECT COUNT(*) FROM journals;
            SELECT COUNT(*) FROM users;
            SELECT COUNT(DISTINCT sf.submission_id) >= 3 FROM submission_files sf
                JOIN submissions s ON s.submission_id = sf.submission_id
                JOIN journals j ON j.journal_id = s.context_id WHERE j.path = '\''vestnik-it'\'';
        "
')
if [[ "$counts" != $'1\n1\n'* ]]; then
    printf '%s\n' 'Expected one journal at vestnik-it. Snapshot not changed.' >&2
    exit 1
fi
if [[ "$seed_dir" == seed && "$counts" != $'1\n1\n12\n1' ]]; then
    printf '%s\n' 'Expected one journal at vestnik-it, 12 test accounts and at least three submissions with files. Seed not changed.' >&2
    exit 1
fi

dump_database "$staging" seed
dump_files "$staging"
check_archives "$staging"
seed_hashes "$staging" > "$staging/SHA256SUMS"
for file in db.sql.gz files.tar.gz SHA256SUMS; do
    mv -- "$staging/$file" "$seed_dir/$file"
done
printf 'Saved db.sql.gz, files.tar.gz and SHA256SUMS in %s/.\n' "$seed_dir"
