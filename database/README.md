# Migraciones iniciales

- `20260327_000001_add_password_hash_to_usuarios.sql`: agrega la columna `password_hash` para migracion gradual de autenticacion.
- `20260327_000002_modernize_legacy_schema.sql`: primer lote para `utf8mb4`, `InnoDB` y mayor capacidad de password.

Aplica cada archivo despues de hacer backup de la base `icontpos`.

