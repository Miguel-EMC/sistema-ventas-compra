# ventaspos

Monorepo con:

- `apps/api`: Laravel 13
- `apps/web`: Angular 21
- `docs`: documentacion funcional y tecnica

La raiz ya no contiene runtime de PHP legacy ni builds del frontend antiguo. La ejecucion se hace con Docker.

## Estructura

```text
ventaspos/
  apps/
    api/
      .env
      .env.example
    web/
      .env
      .env.example
  docs/
  docker-compose.yml
```

## Ejecutar con Docker

1. Revisa los `.env` por app:

   - `apps/api/.env`
   - `apps/web/.env`

2. Levanta el stack:

   ```bash
   sh scripts/start.sh
   ```

   Si lo quieres en segundo plano:

   ```bash
   sh scripts/start.sh --detach
   ```

3. URLs:

   - Web: `http://localhost:8080`
   - API: `http://localhost:8001`
   - PostgreSQL: disponible solo dentro de la red de Docker para `api`

## Variables por app

### `apps/api/.env`

- configuracion Laravel
- credenciales PostgreSQL
- dominios stateful de Sanctum

### `apps/web/.env`

- `API_ORIGIN`
- variables runtime del frontend servidas desde `env.js`

## Notas

- La API arranca en Docker sobre PostgreSQL.
- El frontend se compila en Docker y lee su config runtime desde `apps/web/.env`.
- La documentacion de migracion sigue en `docs/`.
- Usuario admin sembrado al arrancar:
  - usuario: `admin`
  - clave: `password`
