# ventaspos

Primera fase de modernizacion gradual para PHP 8.5.

## Que cambia en esta fase

- Configuracion centralizada con `.env`
- Bootstrap moderno en `bootstrap/app.php`
- Autoload Composer y herramientas de calidad
- Login por `POST` con sesion y CSRF
- Compatibilidad temporal para controladores/vistas legacy
- Capa PDO nueva para auth, menu, settings, uploads y repositorios base
- Shim `mysqli` para que el codigo legacy siga funcionando sin la extension `mysqli`
- `public/assets` reducido a assets puente minimos (`app.css`, `app.js`, logos y uploads`)
- Core UI propio en SCSS con compilacion a `public/assets/css/app.css`
- Layout global sin Bootstrap y con JS propio para sidebar, dropdowns, modales y tabs

## Arranque local

1. Instala dependencias:

   ```bash
   composer install
   npm install
   ```

2. Revisa `.env` y ajusta `APP_URL`, `APP_ASSET_URL` y credenciales de base.

3. Compila el CSS del template:

   ```bash
   npm run build:css
   ```

4. Asegura que Apache apunte al proyecto o a `public/` segun tu despliegue.

5. Si tu base todavia no tiene `password_hash`, aplica las migraciones SQL de `database/migrations/`.

## Migraciones recomendadas

- `database/migrations/20260327_000001_add_password_hash_to_usuarios.sql`
- `database/migrations/20260327_000002_modernize_legacy_schema.sql`

Haz backup antes de aplicarlas.

## Validaciones usadas

```bash
vendor/bin/phpunit --testdox
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/php-cs-fixer fix
vendor/bin/rector process --dry-run
```

## Estado actual

- La autenticacion nueva ya usa hash y migra usuarios legacy al iniciar sesion.
- El shell legacy ya quedo reducido a un puente delgado de autenticacion y redirecciones hacia Angular.
- La logica vieja basada en `Model/Conexion.php` ya fue retirada del runtime principal.
- La UI nueva vive en `resources/scss/` y cualquier cambio visual global debe salir de ahi.
- `public/assets` ya no carga vendors legacy: solo conserva el core minimo y `fotoproducto/`.

## Base nueva de migracion

Tambien ya existe una base paralela para la migracion a stack moderno:

- API Laravel 13: `apps/api`
- Frontend Angular 21: `apps/web`
- Plan general: `docs/laravel-angular-postgresql-migration-plan.md`
- Mapa de dominio: `docs/domain-map.md`
- Propuesta de esquema PostgreSQL: `docs/postgresql-schema-proposal.md`
- Roadmap API-first: `docs/api-first-roadmap.md`

### Comandos utiles

```bash
npm run start:web
npm run build:web
composer --working-dir=apps/api run-script dev
composer --working-dir=apps/api install
composer --working-dir=apps/api run-script test
```

### Nota sobre PostgreSQL

La base nueva esta pensada para PostgreSQL 18, pero tu PHP local todavia necesita habilitar `pdo_pgsql` y `pgsql` para conectar Laravel directamente a Postgres.

### Credenciales base de la API nueva

- usuario: `admin`
- password: `password`

### Modulos ya migrados en la base nueva

- `Auth + Users + Roles`
  - login con token
  - `me`
  - logout
  - CRUD de usuarios
  - listado de roles
- `Catalog`
  - CRUD de categorias de producto
  - CRUD de productos
  - ajuste manual de stock con movimientos
- `Assets`
  - CRUD de categorias de activo
  - CRUD de activos internos
- `Customers`
  - CRUD de clientes
  - consulta para perfiles autenticados
- `Suppliers`
  - CRUD de proveedores
  - consulta para perfiles autenticados

### Frontend Angular ya conectado a la API nueva

- `Login`
- `Dashboard`
- `Usuarios`
- `Productos`
- `Activos`
- `Clientes`
- `Proveedores`

## Siguiente bloque recomendado

- `Ventas POS + borradores + checkout`
- luego `Caja + reportes`
