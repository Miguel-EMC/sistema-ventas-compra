# Plan de limpieza del template UI

## Objetivo
Dejar un template general reutilizable para todo el panel, con un solo core visual, sin Bootstrap en el layout global y con una fuente unica de estilos mantenida en SCSS.

## Principios
- Un solo CSS compilado para el shell del admin, login y vistas legacy.
- Cargar plugins visuales solo cuando una pantalla realmente los necesita.
- No depender de Bootstrap para grid, modales, tabs, dropdowns ni botones.
- Mantener solo los icon packs realmente usados por las vistas actuales.
- No volver a mezclar estilos de dashboard, charts, sliders, calendarios y widgets dentro del layout general.

## Core actual
- Fuente SCSS: `resources/scss/app.scss`
- Partials SCSS:
  - `resources/scss/_tokens.scss`
  - `resources/scss/_base.scss`
  - `resources/scss/_layout.scss`
  - `resources/scss/_components.scss`
  - `resources/scss/_pages.scss`
- CSS compilado: `public/assets/css/app.css`
- JS global del shell y compatibilidad legacy: `public/assets/js/app.js`
- Head compartido: `Views/Head.php`
- Librerias compartidas: `Views/LibraryJs.php`

## Lo que ya se limpio
- Se removio Bootstrap del layout global.
- Se removieron del layout global los CSS legacy de `style.css`, `style-responsive.css`, `style_pru.css`, `widgets.css`, `owl.carousel.css`, `fullcalendar`, `jvectormap`, `xcharts`, `ngDialog`, `basicModal`, `jquery-ui` y otros equivalentes.
- Se removieron del layout global los JS legacy de `jquery-ui`, `nicescroll`, `owl.carousel`, `scripts.js`, `morris`, `zabuto_calendar`, `jvectormap`, `xcharts` y similares.
- Se dejo un shell minimo con:
  - `font-awesome.min.css`
  - `elegant-icons-style.css`
  - `app.css` compilado desde SCSS
- Se dejo un core JS minimo con:
  - `jquery.js`
  - `jquery.dataTables.min.js`
  - `ajax.js`
  - `ajaxPos.js`
  - `jquery.printPage.js`
  - `app.js`
- `app.js` ahora maneja:
  - sidebar responsivo
  - dropdown de usuario
  - modales locales y remotos
  - tabs legacy
  - DataTables basico
  - botones de impresion

## Estructura recomendada para seguir
### Global
- `Views/Head.php`
- `Views/LibraryJs.php`
- `resources/scss/`
- `public/assets/css/app.css`
- `public/assets/js/app.js`

### Opcional por pantalla
- `extraStyles`
- `extraScripts`
- `pageInlineScripts`

Cada vista que necesite un plugin especial debe declararlo localmente, no desde el layout general.

## Comandos de trabajo
- Instalar dependencias de estilos:
  - `npm install`
- Compilar el CSS:
  - `npm run build:css`
- Modo watch para UI:
  - `npm run watch:css`

## Fases siguientes
### Fase 1
- Revisar `UsuarioViews.php`, `ProductoViews.php`, `ClienteViews.php`, `ProveedorViews.php`, `PedidoViews.php` y `VentasViews.php`.
- Reemplazar estilos inline por clases del core SCSS.
- Unificar espaciados, formularios, tablas y acciones de modal.
- Mover cualquier comportamiento JS visual hacia `public/assets/js/app.js` o assets del modulo.

### Fase 2
- Auditar modulos de reportes.
- Decidir si los charts legacy siguen o se migran a una libreria unica.
- Cargar CSS y JS de reportes solo desde esas pantallas.

### Fase 3
- Limpiar componentes repetidos de subida de imagen.
- Crear partials reutilizables para:
  - tablas de modulo
  - breadcrumbs
  - encabezado de acciones
  - formularios de modal

### Fase 4
- Eliminar definitivamente archivos legacy sin uso desde el layout compartido.
- Documentar convenciones de nombres para clases y partials.

## Regla de mantenimiento
Si una pantalla nueva necesita una libreria visual, primero se evalua si:
- entra en `resources/scss/app.scss` porque es del shell general
- o vive como asset opcional solo de ese modulo

Nunca debe volver a cargarse una libreria pesada desde `Views/Head.php` solo por conveniencia.
