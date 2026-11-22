#!/bin/sh
set -eu

# List repository plugins as category/directory.
set -- \
    generic/vestnikExample

fail() {
    printf '%s\n' "$1" >&2
    exit 1
}

cd /var/www/html

for plugin do
    case "$plugin" in
        /*|*/|*/*/*|*[!a-zA-Z0-9/]*) fail "Invalid OJS plugin path: $plugin" ;;
        */*) ;;
        *) fail "Expected category/directory for OJS plugin: $plugin" ;;
    esac

    source="/opt/vestnik/plugins/$plugin"
    target="plugins/$plugin"
    [ -d "plugins/${plugin%/*}" ] || fail "Unknown OJS plugin category: ${plugin%/*}"
    [ -r "$source/version.xml" ] || fail "Missing plugin descriptor: $source/version.xml"

    if [ -L "$target" ]; then
        [ "$(readlink "$target")" = "$source" ] || fail "OJS plugin path already in use: $target"
        continue
    fi

    # A previous Compose bind mount may have left an empty mount point.
    if [ -e "$target" ]; then
        rmdir "$target" 2>/dev/null || fail "OJS plugin path already in use: $target"
    fi
    ln -s "$source" "$target"
done

test -f config.inc.php || cp config.TEMPLATE.inc.php config.inc.php
chown www-data:www-data /var/www/files config.inc.php
chmod 600 config.inc.php
VESTNIK_PLUGINS="$*" su -s /bin/sh -c 'php /opt/vestnik/bootstrap.php' www-data

# Use file locks when running the image under AMD64 emulation.
printf '%s\n' 'Mutex fcntl:/var/lock/apache2 default' > /etc/apache2/conf-enabled/vestnik-local.conf
exec /usr/local/bin/pkp-start
