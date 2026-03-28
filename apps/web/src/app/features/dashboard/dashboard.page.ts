import { Component } from '@angular/core';

@Component({
  selector: 'app-dashboard-page',
  template: `
    <section class="stack">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Dashboard</span>
          <h1 class="page-title">Base funcional para la nueva plataforma.</h1>
          <p class="page-description">
            Este dashboard resume el estado del monorepo nuevo y las primeras decisiones de dominio.
          </p>
        </div>
        <span class="pill">Migracion en fase inicial</span>
      </header>

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Backend</span>
          <strong class="metric-card__value">Laravel 13</strong>
          <span class="metric-card__hint">API versionada con Sanctum y base de migracion lista.</span>
        </article>
        <article class="surface metric-card">
          <span class="metric-card__label">Frontend</span>
          <strong class="metric-card__value">Angular 21</strong>
          <span class="metric-card__hint">Shell inicial con modulos placeholder y rutas lazy.</span>
        </article>
        <article class="surface metric-card">
          <span class="metric-card__label">Base de datos</span>
          <strong class="metric-card__value">PostgreSQL 18</strong>
          <span class="metric-card__hint">Esquema inicial para acceso, catalogo, caja y ventas.</span>
        </article>
      </section>

      <section class="split">
        <article class="surface stack">
          <div class="page-header__copy">
            <span class="page-kicker">Prioridades</span>
            <h2 class="page-title">Lo primero que vamos a construir</h2>
          </div>
          <ul class="feature-list">
            <li>
              <strong>Auth y usuarios</strong>
              Login SPA, roles y permisos reales, sin depender del menu legacy.
            </li>
            <li>
              <strong>Catalogo y activos</strong>
              Separacion clara entre productos vendibles y activos internos.
            </li>
            <li>
              <strong>Clientes y proveedores</strong>
              Terceros migrados para sostener ventas, compras y trazabilidad comercial.
            </li>
            <li>
              <strong>POS nuevo</strong>
              Borradores aislados por usuario y checkout transaccional.
            </li>
          </ul>
        </article>

        <article class="surface surface--muted stack">
          <div class="page-header__copy">
            <span class="page-kicker">Estado</span>
            <h2 class="page-title">Lo que ya quedó montado</h2>
          </div>
          <ul class="feature-list">
            <li>
              <strong>Monorepo base</strong>
              apps/api y apps/web ya existen y conviven con el legacy.
            </li>
            <li>
              <strong>Documentacion de dominio</strong>
              Ya hay plan, mapa de dominio, roadmap API-first y propuesta PostgreSQL.
            </li>
            <li>
              <strong>Primeras migraciones</strong>
              Auth, usuarios, catalogo, activos, clientes y proveedores ya tienen modulo real.
            </li>
          </ul>
        </article>
      </section>
    </section>
  `,
})
export class DashboardPageComponent {}
