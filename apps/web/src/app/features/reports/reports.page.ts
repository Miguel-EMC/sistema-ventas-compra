import { Component } from '@angular/core';

@Component({
  selector: 'app-reports-page',
  template: `
    <section class="stack">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Analitica</span>
          <h1 class="page-title">Reportes consistentes sobre un modelo limpio.</h1>
          <p class="page-description">
            La idea es dejar atras los calculos ambiguos del legacy y construir reportes a partir de
            ventas, caja y stock con trazabilidad clara.
          </p>
        </div>
        <span class="pill">Dominio Reports</span>
      </header>

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Ventas</span>
          <strong class="metric-card__value">Dia / mes / año</strong>
          <span class="metric-card__hint">Totales, cantidad de tickets y ticket promedio.</span>
        </article>
        <article class="surface metric-card">
          <span class="metric-card__label">Catalogo</span>
          <strong class="metric-card__value">Top productos</strong>
          <span class="metric-card__hint">Rotacion, stock critico y productos inactivos.</span>
        </article>
        <article class="surface metric-card">
          <span class="metric-card__label">Caja</span>
          <strong class="metric-card__value">Sesiones y movimientos</strong>
          <span class="metric-card__hint">Aperturas, cierres, ingresos y egresos.</span>
        </article>
      </section>
    </section>
  `,
})
export class ReportsPageComponent {}
