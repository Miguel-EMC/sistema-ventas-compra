# Roadmap API-first

## Objetivo
Construir primero contratos estables en la API para que Angular se monte sobre una base consistente.

## Fase 1
- `GET /api/v1/health` ✅
- `GET /api/v1/auth/me` ✅
- autenticacion Sanctum ✅
- usuarios y roles ✅

## Fase 2
- CRUD de productos ✅
- categorias de producto ✅
- ajustes manuales de stock ✅
- CRUD de activos ✅
- categorias de activo ✅
- CRUD de clientes ✅
- CRUD de proveedores ✅

## Fase 3
- apertura de caja ✅
- borrador de venta ✅
- agregar item ✅
- editar item ✅
- checkout ✅

## Fase 4
- reportes ✅
- compras ✅
- facturacion ✅

## Estado real del repositorio
- la API nueva ya expone `cash`, `sales`, `purchases`, `reports`, `settings` y descargas PDF/CSV
- Angular ya tiene pantallas conectadas para esos modulos
- el bridge de sesion legacy ya fue retirado; PHP solo conserva redirectores residuales para URLs viejas
- la validacion automatizada actual corre sobre SQLite en memoria; PostgreSQL requiere validacion dedicada del entorno

## Criterio de avance
Un modulo no pasa al frontend definitivo hasta tener:
- rutas API definidas
- validaciones
- pruebas feature
- contrato de respuesta consistente
