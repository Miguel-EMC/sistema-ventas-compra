# Mapa de dominio de VentasPOS

## Objetivo
Definir los limites funcionales del sistema nuevo para evitar que la logica de negocio vuelva a mezclarse como en el legacy.

## Modulos principales

### Auth
- login
- logout
- sesion SPA con Sanctum
- usuario autenticado

### Users
- usuarios internos
- roles
- permisos
- estado activo/inactivo

### Settings
- perfil de empresa
- moneda
- idioma
- configuraciones globales
- datos fiscales y resoluciones

### Catalog
- productos comerciales
- categorias
- precios
- stock
- movimientos de stock

### Assets
- activos internos del negocio
- categorias de activo
- cantidad y estado operativo

### Customers
- clientes
- documento
- datos de contacto
- historial comercial

### Suppliers
- proveedores
- documento
- datos de contacto
- compras asociadas

### Sales
- borradores POS por usuario/caja
- checkout
- detalle de venta
- pagos
- factura
- anulacion o devolucion futura

### Cash
- cajas
- apertura
- cierre
- movimientos de ingreso/egreso
- conciliacion

### Purchases
- pedidos
- recepcion de mercaderia
- entrada de stock
- factura proveedor

### Reports
- ventas por periodo
- ventas por producto
- stock actual
- stock critico
- caja por sesion
- rentabilidad basica

## Agregados clave

### Producto
Un producto es un item vendible. Su stock no se guarda como numero manual aislado: se deriva de movimientos.

### Activo
Un activo es un recurso interno del negocio. No participa en la venta POS.

### Borrador de venta
Representa el carrito transitorio de una sesion de caja. Debe pertenecer a un usuario y opcionalmente a una caja abierta.

### Venta
Representa una transaccion confirmada. Debe ser atomica y dejar trazabilidad completa:
- items
- pagos
- cliente
- vendedor
- caja
- rebaja de stock

### Sesion de caja
Representa el periodo operativo de una caja abierta por un usuario.

## Reglas de negocio no negociables
- `Productos` y `Activos` son dominios separados.
- Cada venta confirmada genera movimientos de stock.
- No debe existir una preventa global compartida entre usuarios.
- Caja y ventas deben poder auditarse por usuario y fecha.
- Factura y venta deben compartir el mismo origen transaccional.
