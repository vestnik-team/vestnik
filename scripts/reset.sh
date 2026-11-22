#!/usr/bin/env bash
set -euo pipefail
umask 077

cd -- "$(dirname -- "$0")/.."
source scripts/seed-common.sh

seed_dir=$(seed_directory "$@")
for file in db.sql.gz files.tar.gz SHA256SUMS; do
    if [[ ! -s "$seed_dir/$file" ]]; then
        printf 'Missing %s/%s. Obtain a complete snapshot before resetting OJS.\n' "$seed_dir" "$file" >&2
        exit 1
    fi
done

hashes=$(seed_hashes "$seed_dir")
if [[ "$hashes" != "$(cat "$seed_dir/SHA256SUMS")" ]] || ! check_archives "$seed_dir"; then
    printf '%s\n' 'The seed archives do not match or cannot be read. OJS was not reset.' >&2
    exit 1
fi

# Prepare the local config and volumes if this is the first run.
sh start.sh
backup=$(mktemp -d "${TMPDIR:-/tmp}/vestnik-reset.XXXXXXXX")
docker compose stop ojs
if ! dump_database "$backup" full || ! dump_files "$backup" || ! check_archives "$backup"; then
    docker compose start ojs
    rm -rf -- "$backup"
    printf '%s\n' 'Could not back up local data. OJS was not reset.' >&2
    exit 1
fi

rollback() {
    trap '' INT TERM
    printf 'Reset failed. Restoring the previous state from %s.\n' "$backup" >&2
    if docker compose stop ojs && restore_snapshot "$backup" && sh start.sh; then
        rm -rf -- "$backup"
        printf '%s\n' 'Previous state restored.' >&2
    else
        printf 'Automatic recovery failed. The backup is available in %s.\n' "$backup" >&2
    fi
    exit 1
}

printf 'Local backup created in %s.\n' "$backup"
trap rollback INT TERM
if ! restore_snapshot "$seed_dir" || ! sh start.sh; then
    rollback
fi
trap - INT TERM
rm -rf -- "$backup"
printf 'Restored the OJS database and submission files from %s/.\n' "$seed_dir"
