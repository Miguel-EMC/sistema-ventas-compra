# Plan de limpieza del template UI

## Objetivo
Dejar el puente legacy con un core visual minimo y estable, mientras Angular absorbe el panel completo sin arrastrar vendors ni bundles viejos.

## Principios
- Un solo CSS compilado para cualquier HTML puente que siga vivo.
- Un solo JS global para compatibilidad minima del shell.
- No volver a reintroducir vendors legacy en `public/assets`.
- Mantener `public/assets` solo con archivos que el runtime actual todavia necesite.
- Mover la UI real al frontend Angular y dejar PHP solo como auth/redirect bridge.

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
- Logos puente: `public/assets/img/logoI.png`, `public/assets/img/logo-big.png`
- Uploads legacy publicados: `public/assets/fotoproducto/`

## Lo que ya se limpio
- Se removieron las vistas compartidas del shell legacy (`Views/Head.php`, `Views/LibraryJs.php`, menus y parciales antiguos).
- Se retiraron del runtime los CSS legacy de `style.css`, `style-responsive.css`, `style_pru.css`, `widgets.css`, `owl.carousel.css`, `fullcalendar`, `jvectormap`, `xcharts`, `ngDialog`, `basicModal`, `jquery-ui` y equivalentes.
- Se retiraron del runtime los JS legacy de `jquery-ui`, `nicescroll`, `owl.carousel`, `scripts.js`, `morris`, `zabuto_calendar`, `jvectormap`, `xcharts`, `ajaxPos.js`, `ajaxTrasacction.js` y similares.
- Se eliminaron `public/assets/fonts/` y `public/assets/graphicsBase/`.
- `public/assets` quedo reducido a:
  - `public/assets/css/app.css`
  - `public/assets/js/app.js`
  - `public/assets/img/logoI.png`
  - `public/assets/img/logo-big.png`
  - `public/assets/fotoproducto/`
- `app.js` ahora maneja:
  - sidebar responsivo
  - dropdown de usuario
  - modales locales y remotos
  - normalizacion ligera de modales legacy

## Estructura recomendada para seguir
### Global
- `resources/scss/`
- `public/assets/css/app.css`
- `public/assets/js/app.js`
- `public/assets/img/logoI.png`
- `public/assets/img/logo-big.png`
- `public/assets/fotoproducto/`

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
- Revisar si queda algun HTML PHP que todavia necesite `public/assets/css/app.css` o `public/assets/js/app.js`.
- Reducir `app.js` en cuanto desaparezcan los ultimos modales remotos del puente.
- Mantener cualquier ajuste visual nuevo fuera del bundle Angular solo si el bridge realmente lo necesita.

### Fase 2
- Migrar cualquier exportacion o pantalla suelta que todavia dependa del root PHP.
- Eliminar los stubs puente que ya no tengan trafico funcional.

### Fase 3
- Revisar `fotoproducto/` para separar uploads reales de imagenes demo heredadas, si ya no hacen falta como datos semilla.

### Fase 4
- Eliminar definitivamente `public/assets/css/app.css` y `public/assets/js/app.js` cuando el shell PHP deje de renderizar HTML propio.
- Documentar el cierre final del bridge legacy.

## Regla de mantenimiento
Si algun ajuste nuevo toca el root PHP, primero se evalua si:
- realmente pertenece al bridge legacy
- o debe vivir directamente en Angular/Laravel nuevo

No debe volver a cargarse una libreria pesada dentro de `public/assets` solo por conveniencia.
