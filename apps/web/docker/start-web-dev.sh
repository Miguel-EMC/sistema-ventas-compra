#!/bin/sh
set -eu

LOCK_HASH_FILE="/app/node_modules/.package-lock.hash"
CURRENT_LOCK_HASH="$(sha256sum /app/package-lock.json | awk '{print $1}')"
STORED_LOCK_HASH=""

if [ -f "$LOCK_HASH_FILE" ]; then
  STORED_LOCK_HASH="$(cat "$LOCK_HASH_FILE")"
fi

if [ ! -d /app/node_modules/.bin ] || [ "$CURRENT_LOCK_HASH" != "$STORED_LOCK_HASH" ]; then
  npm install
  printf '%s' "$CURRENT_LOCK_HASH" > "$LOCK_HASH_FILE"
fi

exec npm start -- --host 0.0.0.0 --port 8080 --poll 1000
