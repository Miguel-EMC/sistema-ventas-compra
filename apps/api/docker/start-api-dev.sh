#!/bin/sh
set -eu

LOCK_HASH_FILE="/app/vendor/.composer.lock.hash"
CURRENT_LOCK_HASH="$(cat /app/composer.json /app/composer.lock | sha256sum | awk '{print $1}')"
STORED_LOCK_HASH=""

if [ -f "$LOCK_HASH_FILE" ]; then
  STORED_LOCK_HASH="$(cat "$LOCK_HASH_FILE")"
fi

if [ ! -f /app/vendor/autoload.php ] || [ "$CURRENT_LOCK_HASH" != "$STORED_LOCK_HASH" ]; then
  composer install --no-interaction --prefer-dist
  printf '%s' "$CURRENT_LOCK_HASH" > "$LOCK_HASH_FILE"
fi

exec sh /usr/local/bin/start-api.sh
