import { Component } from '@angular/core';

@Component({
  selector: 'app-settings-page',
  template: `
    <section class="stack">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Configuracion</span>
          <h1 class="page-title">Empresa, moneda, idioma y parametros globales.</h1>
          <p class="page-description">
            Esta area concentrara la configuracion que hoy esta repartida entre datos factura,
            idioma, moneda y otros modulos legacy.
          </p>
        </div>
        <span class="pill">Dominio Settings</span>
      </header>

      <section class="split">
        <article class="surface stack">
          <h2 class="page-title">Configuraciones base</h2>
          <ul class="feature-list">
            <li>
              <strong>company_profiles</strong>
              Datos fiscales y comerciales de la empresa.
            </li>
            <li>
              <strong>currencies</strong>
              Moneda activa, simbolo y precision.
            </li>
            <li>
              <strong>locales</strong>
              Idioma y localizacion por defecto.
            </li>
          </ul>
        </article>

        <article class="surface surface--muted stack">
          <h2 class="page-title">Meta</h2>
          <p class="muted">
            Unificar configuraciones del negocio en una sola experiencia limpia y evitar la
            dispersion actual de menus y pantallas.
          </p>
        </article>
      </section>
    </section>
  `,
})
export class SettingsPageComponent {}
