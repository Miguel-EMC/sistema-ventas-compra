# Propuesta de esquema PostgreSQL inicial

## Version objetivo
- PostgreSQL 18

## Convenciones
- claves primarias `bigint` con `id`
- identificadores publicos `uuid`
- fechas operativas con `timestamp with time zone`
- montos con `numeric(14,2)`
- borrado logico donde haga sentido

## Tablas base

### Acceso
- `users`
- `roles`
- `permissions`
- `permission_role`

### Configuracion
- `company_profiles`
- `currencies`
- `locales`
- `system_settings`

### Catalogo comercial
- `product_categories`
- `products`
- `stock_movements`

### Activos
- `asset_categories`
- `assets`

### Terceros
- `customers`
- `suppliers`

### Caja
- `cash_registers`
- `cash_sessions`
- `cash_movements`

### Ventas
- `sale_drafts`
- `sale_draft_items`
- `sales`
- `sale_items`
- `sale_payments`

## Reglas de modelado

### Stock
No se persiste una unica columna de stock como fuente de verdad. El saldo sale de `stock_movements`.

### Carrito POS
`sale_drafts` reemplaza la preventa global legacy. Un borrador siempre debe vincularse a:
- un usuario
- una caja o sesion operativa si aplica

### Venta confirmada
`sales` es la cabecera.
`sale_items` es el detalle.
`sale_payments` registra medios de pago.

### Caja
Cada `cash_session` nace con apertura, puede tener varios `cash_movements` y termina con cierre.

## Tablas pendientes para fases siguientes
- `invoices`
- `invoice_items`
- `purchase_orders`
- `purchase_order_items`
- `goods_receipts`
- `supplier_invoices`
- `returns`
- `audit_logs`

## Estado actual en codigo
La base de esta propuesta ya esta arrancada en las migraciones nuevas dentro de `apps/api/database/migrations/`.
