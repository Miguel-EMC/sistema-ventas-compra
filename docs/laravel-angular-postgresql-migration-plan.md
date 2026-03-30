# Plan de migracion a Laravel 13 + Angular 21 + PostgreSQL 18

## Objetivo
Migrar `ventaspos` desde su arquitectura PHP legacy a una plataforma moderna, mantenible y escalable basada en:

- Backend API: Laravel 13
- Frontend SPA: Angular 21
- Base de datos: PostgreSQL 18
- Autenticacion web/API: Laravel Sanctum
- UI: Angular con Angular Material, Angular CDK y un design system propio

La migracion debe preservar la operacion del negocio, corregir inconsistencias de logica y evitar una reescritura de golpe.

## Versiones objetivo
Este plan toma como referencia el estado oficial al 28 de marzo de 2026:

- Laravel 13
- Angular 21
- PostgreSQL 18
- PHP 8.5
- Node.js 22 LTS

## Problemas del sistema actual
El sistema legacy mezcla vistas, controladores, acceso a datos y reglas de negocio en el mismo flujo. Los principales problemas detectados son:

- Historicamente, la mayor parte de la logica estaba centralizada en `Model/Conexion.php`.
- Los controladores ejecutan reglas de negocio directamente y dependen de `$_GET`, `$_POST` y sesiones sin una capa de aplicacion clara.
- La estructura actual no separa bien:
  - catalogo comercial
  - activos internos
  - ventas
  - caja
  - configuracion
  - reportes
- La tabla temporal de preventa no esta bien aislada por sesion/cajero.
- El stock comercial no se comporta como un kardex transaccional moderno.
- Existen tablas y reportes con duplicacion o persistencia ambigua, por ejemplo:
  - `preventa`
  - `ventatotal`
  - `datosventa`
  - `datosventatotal`
  - `datosfacturaventa`
  - `datosclienteventa`
- La UI esta fuertemente acoplada al backend legacy.

## Principios de migracion
- No hacer un `big bang rewrite`.
- Migrar por modulos con una estrategia tipo strangler.
- Diseñar primero la logica de negocio y el modelo de datos.
- La nueva API sera la fuente de verdad.
- Angular consumira contratos estables y versionados.
- PostgreSQL sera el unico motor soportado en la plataforma nueva.
- Todo cambio critico debe quedar cubierto por pruebas.

## Arquitectura objetivo

### Repositorio
Se recomienda evolucionar el proyecto actual hacia un monorepo ligero:

```text
ventaspos/
  apps/
    api/                    # Laravel 13
    web/                    # Angular 21
  packages/
    contracts/              # DTOs, esquemas OpenAPI/JSON Schema, tipos compartidos
  database/
    legacy/                 # dumps y scripts de apoyo al sistema viejo
    mappings/               # mapeos legacy -> nuevo modelo
  docs/
  scripts/
```

### Backend Laravel
`apps/api` debe organizarse por dominio, no por tipo de archivo plano:

```text
app/
  Domain/
    Auth/
    Users/
    Catalog/
    Assets/
    Sales/
    Customers/
    Suppliers/
    Cash/
    Purchases/
    Billing/
    Reports/
    Settings/
  Application/
    UseCases/
    DTOs/
    Services/
  Infrastructure/
    Persistence/
    Http/
    Security/
    Files/
    Reports/
  Support/
```

### Frontend Angular
`apps/web` debe trabajar como SPA administrativa moderna:

```text
src/app/
  core/
    auth/
    http/
    layout/
    guards/
    interceptors/
  shared/
    ui/
    forms/
    dialogs/
    tables/
    pipes/
  features/
    auth/
    dashboard/
    users/
    settings/
    products/
    assets/
    customers/
    suppliers/
    sales/
    cash/
    purchases/
    reports/
```

## Stack funcional recomendado

### Backend
- Laravel 13
- Eloquent para la mayor parte del dominio
- Query Builder para reportes pesados
- Laravel Sanctum para autenticacion SPA
- Form Requests para validacion
- Policies y Gates para permisos
- Laravel Events para acciones de negocio
- Jobs y Queues para PDF, exportaciones y procesos pesados

### Frontend
- Angular 21 con componentes standalone
- Signals para estado local
- RxJS solo donde aporte valor real
- Angular Router con lazy loading por modulo
- Angular Material como base actual del sistema
- Angular CDK para overlays, menus y accesibilidad
- Design system propio para:
  - shell
  - tablas
  - formularios
  - dialogs
  - cards
  - filtros
  - estados vacios

### Base de datos
- PostgreSQL 18
- Migraciones versionadas desde Laravel
- Indices, constraints y claves foraneas reales
- Columnas `uuid` o `bigint` segun la estrategia que definamos
- `timestamp with time zone` para eventos de negocio
- `numeric(14,2)` o equivalente para montos
- `check constraints` para reglas simples

## Dominios del negocio

### 1. Auth y usuarios
Reemplaza:
- `usuarios`
- control de acceso acoplado a menu

Nuevo modelo:
- `users`
- `roles`
- `permissions`
- `role_user`
- `permission_role`
- `sessions` si se necesita auditoria extendida

Reglas:
- Login por email o username
- Password hashing moderno
- Sesion segura
- Permisos por rol y capability, no por menu hardcodeado

### 2. Configuracion
Reemplaza:
- `datos`
- `moneda`
- `idioma`
- `dosificacion`

Nuevo modelo:
- `company_profiles`
- `currencies`
- `locales`
- `tax_resolutions`
- `system_settings`

Reglas:
- Configuracion editable por administracion
- Historial de cambios para resoluciones fiscales si aplica

### 3. Catalogo comercial
Reemplaza:
- `producto`
- `tipoproducto`

Nuevo modelo:
- `products`
- `product_categories`
- `product_prices`
- `product_stock_levels`
- `stock_movements`

Reglas:
- El stock no debe editarse como numero suelto sin traza
- Toda entrada o salida debe generar movimiento
- Debe existir costo, precio, estado y trazabilidad

### 4. Activos internos
Reemplaza:
- `activos`

Nuevo modelo:
- `assets`
- `asset_categories`
- `asset_movements`
- `asset_maintenance_logs` si luego se necesita

Reglas:
- Los activos no se venden por caja
- Se separan del stock comercial

### 5. Clientes y proveedores
Reemplaza:
- `cliente`
- `proveedor`

Nuevo modelo:
- `customers`
- `suppliers`
- `customer_contacts`
- `supplier_contacts`

Reglas:
- Documentos unicos
- Estado activo/inactivo
- Historial minimo de relacion comercial

### 6. Ventas POS
Reemplaza:
- `preventa`
- `ventatotal`
- `datosventa`
- `datosventatotal`
- `datosfacturaventa`
- `datosclienteventa`

Nuevo modelo:
- `sales`
- `sale_items`
- `sale_payments`
- `sale_drafts`
- `sale_draft_items`
- `invoices`
- `invoice_items`
- `customer_snapshots`

Reglas:
- El borrador de venta debe pertenecer a una sesion o caja abierta
- La venta confirmada debe ser atomica
- Debe descontar stock mediante movimientos
- Debe registrar quien vende, desde que caja y en que momento
- Debe soportar con factura y sin factura sin duplicar logica

### 7. Caja
Reemplaza:
- `gastos`

Nuevo modelo:
- `cash_registers`
- `cash_sessions`
- `cash_movements`

Reglas:
- Apertura de caja
- movimientos de ingreso y egreso
- cierre y conciliacion
- trazabilidad por usuario

### 8. Compras y pedidos
Reemplaza:
- `pedido`

Nuevo modelo:
- `purchase_orders`
- `purchase_order_items`
- `goods_receipts`
- `supplier_invoices`

Reglas:
- Registrar pedido al proveedor
- Confirmar recepcion
- Generar entrada de stock

### 9. Reportes
Reemplaza:
- consultas directas dispersas en reportes legacy

Nuevo modelo:
- vistas SQL o consultas materializadas para analitica pesada
- capa de reportes con filtros consistentes

Reportes base:
- ventas por dia, mes y anio
- ventas por producto
- top clientes
- movimientos de caja
- rentabilidad basica
- stock actual y stock critico

## Mapeo legacy -> nuevo

| Legacy | Nuevo dominio |
| --- | --- |
| `usuarios` | `users`, `roles`, `permissions` |
| `producto` | `products`, `product_prices`, `stock_movements` |
| `tipoproducto` | `product_categories` |
| `activos` | `assets` |
| `cliente` | `customers` |
| `proveedor` | `suppliers` |
| `preventa` | `sale_drafts`, `sale_draft_items` |
| `ventatotal` + `datosventa` + `datosventatotal` | `sales`, `sale_items`, `sale_payments` |
| `datosfacturaventa` | `invoices`, `invoice_items` |
| `datosclienteventa` | `customer_snapshots` |
| `gastos` | `cash_movements` |
| `pedido` | `purchase_orders`, `purchase_order_items` |

## API objetivo

### Autenticacion
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`

### Usuarios
- `GET /api/v1/users`
- `POST /api/v1/users`
- `PATCH /api/v1/users/{id}`
- `DELETE /api/v1/users/{id}`

### Productos
- `GET /api/v1/products`
- `POST /api/v1/products`
- `PATCH /api/v1/products/{id}`
- `DELETE /api/v1/products/{id}`
- `POST /api/v1/products/{id}/stock-adjustments`

### Activos
- `GET /api/v1/assets`
- `POST /api/v1/assets`
- `PATCH /api/v1/assets/{id}`
- `DELETE /api/v1/assets/{id}`

### Ventas
- `POST /api/v1/sales/drafts`
- `POST /api/v1/sales/drafts/{id}/items`
- `PATCH /api/v1/sales/drafts/{id}/items/{itemId}`
- `DELETE /api/v1/sales/drafts/{id}/items/{itemId}`
- `POST /api/v1/sales/checkout`
- `GET /api/v1/sales/{id}`

### Caja
- `POST /api/v1/cash/sessions/open`
- `POST /api/v1/cash/movements`
- `POST /api/v1/cash/sessions/{id}/close`

### Reportes
- `GET /api/v1/reports/sales-summary`
- `GET /api/v1/reports/products`
- `GET /api/v1/reports/cash`
- `GET /api/v1/reports/inventory`

## Decisiones de negocio a corregir durante la migracion
- El stock debe descontarse automaticamente al confirmar una venta.
- El borrador de venta no puede ser global.
- La consolidacion manual debe reevaluarse:
  - o desaparece
  - o se reemplaza por estados de venta bien definidos
- Los reportes no deben depender de tablas duplicadas o snapshots ambiguos.
- Facturacion y venta deben compartir un solo flujo transaccional.
- Deben existir reglas claras para:
  - anulacion
  - devolucion
  - ajuste de stock
  - cierre de caja

## Estrategia de migracion por fases

### Fase 0. Descubrimiento y saneamiento
- Congelar nuevas features en el legacy salvo correcciones criticas.
- Documentar flujos reales del negocio.
- Mapear tablas y dependencias actuales.
- Definir casos borde:
  - venta sin factura
  - venta con factura
  - cancelacion
  - cliente ocasional
  - cambios de precio
  - caja del dia

### Fase 1. Base tecnica nueva
- Crear `apps/api` con Laravel 13.
- Crear `apps/web` con Angular 21.
- Levantar PostgreSQL 18 para desarrollo.
- Configurar CI minima:
  - tests backend
  - lint frontend
  - typecheck
  - build
- Definir OpenAPI inicial.

### Fase 2. Auth, usuarios y configuracion
- Migrar autenticacion.
- Migrar usuarios, roles y permisos.
- Migrar configuraciones del negocio.
- Reemplazar el login legacy por Angular.

### Fase 3. Catalogo comercial y activos
- Migrar productos y categorias.
- Migrar activos internos.
- Implementar stock_movements y ajustes.
- Cerrar la brecha semantica entre productos y activos.

### Fase 4. Clientes, proveedores y compras
- Migrar clientes y proveedores.
- Migrar pedidos y compras.
- Integrar entradas de stock por compras recibidas.

### Fase 5. Ventas POS
- Implementar draft de venta aislado por usuario/caja.
- Implementar checkout transaccional.
- Integrar pago, factura y rebaja de stock.
- Reemplazar `Ventas.php` por el nuevo POS Angular.

### Fase 6. Caja y reportes
- Implementar apertura y cierre de caja.
- Implementar reportes basados en el nuevo modelo.
- Validar cifras contra el sistema legacy.

### Fase 7. Apagado del legacy
- Desactivar controladores viejos modulo por modulo.
- Dejar solo adaptadores de redireccion si hace falta.
- Congelar acceso de escritura al sistema anterior.

## Estrategia de migracion de datos

### Extraccion
- Exportar datos del sistema actual desde MySQL/MariaDB.
- Limpiar codificaciones, fechas invalidas y columnas ambiguas.

### Transformacion
- Crear scripts de mapeo para:
  - usuarios
  - clientes
  - proveedores
  - productos
  - activos
  - ventas historicas

### Carga
- Cargar primero catalogos maestros.
- Cargar luego historico de ventas y caja.
- Verificar totales por rango de fechas.

### Validacion
- Comparar:
  - cantidad de usuarios
  - cantidad de productos
  - saldos de stock
  - ventas por dia
  - ventas por mes
  - total de caja por periodo

## Frontend Angular propuesto

### Shell
- Layout con sidebar, topbar y contenido central
- Modo desktop y tablet bien resuelto
- Tema claro por defecto
- Sistema de tokens visuales

### Componentes base
- `app-page-header`
- `app-data-table`
- `app-empty-state`
- `app-confirm-dialog`
- `app-form-dialog`
- `app-stat-card`
- `app-filter-bar`
- `app-pos-product-grid`
- `app-sale-summary`

### Modulos prioritarios
- Login
- Dashboard
- Usuarios
- Configuracion
- Productos
- Activos
- Clientes
- Proveedores
- Ventas POS
- Reportes

## Pruebas necesarias

### Backend
- Unit tests para reglas de negocio
- Feature tests para API
- Tests transaccionales para ventas, stock y caja

### Frontend
- Unit tests para componentes criticos
- E2E para:
  - login
  - CRUD de producto
  - venta completa
  - apertura y cierre de caja

### Datos
- Scripts de comparacion entre legacy y nuevo
- Pruebas de consistencia de stock y reportes

## Riesgos
- La logica real del negocio puede no coincidir al 100% con lo que refleja el codigo.
- Los reportes legacy pueden estar calculando sobre datos ambiguos.
- Si no definimos bien el flujo POS nuevo, Angular solo cambiaria la UI pero no el problema de fondo.
- Migrar historico de ventas puede requerir decisiones contables y fiscales.

## Recomendacion de implementacion
La mejor ruta para este proyecto es:

1. Mantener el legacy solo como sistema de referencia temporal.
2. Empezar ya con `Laravel 13 + PostgreSQL 18`.
3. Construir Angular 21 una vez fijados los contratos API y los modulos base.
4. Migrar primero lo estructural:
   - auth
   - configuracion
   - catalogo
   - clientes/proveedores
5. Dejar `ventas`, `caja` y `reportes` para una fase controlada, porque ahi vive la logica mas sensible.

## Entregables sugeridos

### Sprint 1
- Base Laravel 13
- Base Angular 21
- PostgreSQL 18
- Login
- Usuarios
- Roles
- Configuracion del negocio

### Sprint 2
- Productos
- Categorias
- Activos
- Clientes
- Proveedores

### Sprint 3
- POS nuevo
- Caja
- Facturacion base
- Ajustes de stock

### Sprint 4
- Reportes
- Migracion historica
- Apagado parcial del legacy

## Siguiente paso recomendado
Antes de escribir codigo nuevo, conviene producir tres documentos adicionales:

1. `docs/domain-map.md`
2. `docs/postgresql-schema-proposal.md`
3. `docs/api-first-roadmap.md`

Esos tres documentos nos permitirian arrancar la implementacion sin improvisar el dominio.
