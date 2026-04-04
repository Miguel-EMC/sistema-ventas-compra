#!/bin/sh
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)

cd "$ROOT_DIR"

COMPOSE_ARGS="-f docker-compose.dev.yml"
MODE_LABEL="desarrollo"
DETACH=0
BUILD=0

while [ "$#" -gt 0 ]; do
  case "$1" in
    -d|--detach)
      DETACH=1
      ;;
    -b|--build|--rebuild)
      BUILD=1
      ;;
    -p|--prod|production)
      COMPOSE_ARGS="-f docker-compose.yml"
      MODE_LABEL="produccion"
      BUILD=1
      ;;
    *)
      echo "Uso: sh scripts/start.sh [--detach|-d] [--build|-b] [--prod|-p]" >&2
      exit 1
      ;;
  esac
  shift
done

echo "Levantando VentasPOS con Docker ($MODE_LABEL)..."
echo "Web:   http://localhost:8080"
echo "API:   http://localhost:8001"
echo "Admin: admin"
echo "Clave: password"

if [ "$MODE_LABEL" = "desarrollo" ]; then
  echo "Hot reload: activo en web y codigo montado en api"
  echo "Build de imagenes: omitido por defecto"
fi

UP_ARGS="up"

if [ "$BUILD" -eq 1 ]; then
  UP_ARGS="$UP_ARGS --build"
fi

if [ "${DETACH:-0}" -eq 1 ]; then
  UP_ARGS="$UP_ARGS -d"
fi

# shellcheck disable=SC2086
exec docker compose $COMPOSE_ARGS $UP_ARGS
