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
- `public/assets` como fuente publica de assets legacy
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
- El modelo `Model/Conexion.php` sigue existiendo como puente de compatibilidad.
- Todavia quedan controladores y vistas legacy por mover a `app/` en siguientes fases.
- La UI nueva vive en `resources/scss/` y cualquier cambio visual global debe salir de ahi.
