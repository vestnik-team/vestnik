# Shared Docker Compose operations for capturing and restoring the test journal.

seed_directory() {
    if [[ $# == 0 ]]; then
        printf '%s\n' seed
    elif [[ $# == 1 && "$1" == --draft ]]; then
        printf '%s\n' seed/draft
    else
        printf 'Usage: %s [--draft]\n' "$0" >&2
        return 2
    fi
}

dump_database() {
    local directory=$1 mode=$2
    docker compose exec -T db sh -ec '
        export MYSQL_PWD="$MARIADB_PASSWORD"
        if [ "$1" = seed ]; then
            set -- --ignore-table-data="$MARIADB_DATABASE.sessions" \
                --ignore-table-data="$MARIADB_DATABASE.jobs" \
                --ignore-table-data="$MARIADB_DATABASE.failed_jobs" \
                --ignore-table-data="$MARIADB_DATABASE.password_reset_tokens"
        else
            set --
        fi
        exec mariadb-dump --user="$MARIADB_USER" --single-transaction --quick \
            --skip-lock-tables --no-tablespaces --hex-blob "$@" -- "$MARIADB_DATABASE"
    ' sh "$mode" | gzip -n > "$directory/db.sql.gz"
}

dump_files() {
    docker compose run --rm --no-deps -T --entrypoint tar ojs \
        -C /var/www/files -czf - . > "$1/files.tar.gz"
}

check_archives() {
    gzip -t "$1/db.sql.gz" && tar -tzf "$1/files.tar.gz" >/dev/null
}

sha256_file() {
    if command -v sha256sum >/dev/null; then
        sha256sum < "$1"
    else
        shasum -a 256 < "$1"
    fi | cut -d ' ' -f 1
}

seed_hashes() {
    local file hash
    for file in db.sql.gz files.tar.gz; do
        hash=$(sha256_file "$1/$file") || return
        printf '%s  %s\n' "$hash" "$file"
    done
}

restore_snapshot() {
    local directory=$1
    docker compose exec -T db sh -ec '
        export MYSQL_PWD="$MARIADB_PASSWORD"
        name=$(printf "%s" "$MARIADB_DATABASE" | sed "s/\`/\`\`/g")
        printf "DROP DATABASE IF EXISTS \`%s\`;\nCREATE DATABASE \`%s\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n" "$name" "$name" |
            mariadb --user="$MARIADB_USER"
    ' || return

    gzip -dc "$directory/db.sql.gz" | docker compose exec -T db sh -ec '
        export MYSQL_PWD="$MARIADB_PASSWORD"
        exec mariadb --user="$MARIADB_USER" --database="$MARIADB_DATABASE"
    ' || return

    docker compose run --rm --no-deps -T --entrypoint sh ojs -ec '
        find /var/www/files -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +
        tar -xzf - -C /var/www/files
        chown -R www-data:www-data /var/www/files
        find /var/www/html/cache -maxdepth 1 -type f -name "fc-*" -delete
    ' < "$directory/files.tar.gz"
}
