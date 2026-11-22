#!/bin/sh
set -eu

docker compose up -d
url=$(docker compose exec -T ojs printenv OJS_BASE_URL)
printf 'Ожидаем ответа OJS'
attempt=0
while [ "$attempt" -lt 180 ]; do
    if curl --noproxy '*' --silent --fail --location --max-time 4 --output /dev/null "${url%/}/"; then
        printf '\n\nOJS готов → %s\n' "$url"
        exit 0
    fi
    printf '.'
    attempt=$((attempt + 1))
    sleep 2
done
printf '\nСайт пока не ответил. Посмотрите журнал: docker compose logs --tail=80 ojs db\n' >&2
exit 1
