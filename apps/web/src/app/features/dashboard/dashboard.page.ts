import { Component, inject, signal } from '@angular/core';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { DashboardApiService } from './dashboard.api';
import { DashboardLowStockProduct, DashboardSummary } from './dashboard.types';

@Component({
  selector: 'app-dashboard-page',
  imports: [MatProgressBarModule, RouterLink],
  template: `
    <section class="stack dashboard-page">
      <header class="surface dashboard-hero">
        <div class="dashboard-hero__copy">
          <span class="page-kicker">Dashboard</span>
          <h1 class="page-title">Resumen operativo del dia</h1>
          <p class="page-description">
            Una vista sobria para seguir ventas, caja, clientes y stock sin ruido visual.
          </p>
        </div>

        @if (dashboard(); as dashboard) {
          <div class="dashboard-hero__side">
            <div class="dashboard-balance">
              <small>Ventas de hoy</small>
              <strong>{{ formatCurrency(dashboard.summary.sales_today_total) }}</strong>
              <span>{{ dashboard.summary.sales_today_count }} transaccion(es) registradas.</span>
            </div>
            <div class="dashboard-balance dashboard-balance--muted">
              <small>Cajas abiertas</small>
              <strong>{{ dashboard.summary.open_cash_sessions }}</strong>
              <span>Sesiones activas en operacion.</span>
            </div>
          </div>
        }
      </header>

      @if (loading()) {
        <mat-progress-bar mode="indeterminate"></mat-progress-bar>
      }

      @if (error()) {
        <article class="surface surface--muted stack dashboard-message">
          <span class="page-kicker">Estado</span>
          <strong>{{ error() }}</strong>
        </article>
      }

      @if (legacyNotice()) {
        <article class="surface surface--muted stack dashboard-message">
          <span class="page-kicker">Migracion</span>
          <strong>{{ legacyNotice() }}</strong>
        </article>
      }

      @if (dashboard(); as dashboard) {
        <section class="dashboard-metrics">
          <article class="surface dashboard-metric">
            <span class="dashboard-metric__label">Ventas del dia</span>
            <strong class="dashboard-metric__value">{{
              formatCurrency(dashboard.summary.sales_today_total)
            }}</strong>
            <span class="dashboard-metric__hint">
              {{ dashboard.summary.sales_today_count }} venta(s)
            </span>
          </article>

          <article class="surface dashboard-metric">
            <span class="dashboard-metric__label">Ventas del mes</span>
            <strong class="dashboard-metric__value">{{
              formatCurrency(dashboard.summary.sales_month_total)
            }}</strong>
            <span class="dashboard-metric__hint">
              {{ dashboard.summary.sales_month_count }} venta(s)
            </span>
          </article>

          <article class="surface dashboard-metric">
            <span class="dashboard-metric__label">Clientes activos</span>
            <strong class="dashboard-metric__value">{{ dashboard.summary.active_customers }}</strong>
            <span class="dashboard-metric__hint">Base comercial operativa</span>
          </article>

          <article class="surface dashboard-metric">
            <span class="dashboard-metric__label">Cajas abiertas</span>
            <strong class="dashboard-metric__value">{{ dashboard.summary.open_cash_sessions }}</strong>
            <span class="dashboard-metric__hint">Operacion en curso</span>
          </article>

          <article class="surface dashboard-metric">
            <span class="dashboard-metric__label">Stock critico</span>
            <strong class="dashboard-metric__value">{{ dashboard.summary.low_stock_products }}</strong>
            <span class="dashboard-metric__hint">Productos por revisar</span>
          </article>
        </section>

        <section class="dashboard-layout">
          <article class="surface dashboard-panel dashboard-panel--wide">
            <header class="dashboard-panel__header">
              <div>
                <span class="page-kicker">Ventas</span>
                <h2 class="dashboard-panel__title">Ventas recientes</h2>
              </div>
              <a class="btn" [routerLink]="['/sales']">Ir al POS</a>
            </header>

            <div class="dashboard-panel__body">
              @if (dashboard.recent_sales.length === 0) {
                <p class="muted">Todavia no hay ventas cerradas para mostrar.</p>
              } @else {
                <div class="dashboard-list">
                  @for (sale of dashboard.recent_sales; track sale.id) {
                    <article class="dashboard-row">
                      <div class="dashboard-row__copy">
                        <strong>{{ sale.customer_name || 'Consumidor final' }}</strong>
                        <span>
                          {{ sale.items_count }} item(s) ·
                          {{ formatPaymentMethods(sale.payment_methods) }}
                        </span>
                        <small>
                          {{ sale.register_name || 'Sin caja' }} · {{ formatDateTime(sale.sold_at) }}
                        </small>
                      </div>
                      <div class="dashboard-row__amount">
                        {{ formatCurrency(sale.grand_total) }}
                      </div>
                    </article>
                  }
                </div>
              }
            </div>
          </article>

          <article class="surface dashboard-panel">
            <header class="dashboard-panel__header">
              <div>
                <span class="page-kicker">Inventario</span>
                <h2 class="dashboard-panel__title">Stock critico</h2>
              </div>
              <a class="btn" [routerLink]="['/products']">Ver productos</a>
            </header>

            <div class="dashboard-panel__body">
              @if (dashboard.low_stock_products.length === 0) {
                <p class="muted">No hay productos en estado critico.</p>
              } @else {
                <div class="dashboard-list dashboard-list--compact">
                  @for (product of dashboard.low_stock_products; track product.id) {
                    <article class="dashboard-row dashboard-row--stacked">
                      <div class="dashboard-row__copy">
                        <strong>{{ product.name }}</strong>
                        <small>
                          Faltan {{ formatNumber(stockGap(product)) }} {{ product.unit }} para volver
                          al minimo.
                        </small>
                      </div>
                      <div class="dashboard-tags">
                        <span class="dashboard-tag">Actual {{ formatNumber(product.current_stock) }}</span>
                        <span class="dashboard-tag dashboard-tag--accent">
                          Minimo {{ formatNumber(product.minimum_stock) }}
                        </span>
                      </div>
                    </article>
                  }
                </div>
              }
            </div>
          </article>

          <article class="surface dashboard-panel dashboard-panel--soft">
            <header class="dashboard-panel__header">
              <div>
                <span class="page-kicker">Accesos</span>
                <h2 class="dashboard-panel__title">Acciones rapidas</h2>
              </div>
            </header>

            <div class="dashboard-panel__body stack">
              <div class="dashboard-tags">
                <span class="dashboard-tag">Hoy {{ dashboard.summary.sales_today_count }} venta(s)</span>
                <span class="dashboard-tag">Mes {{ dashboard.summary.sales_month_count }} venta(s)</span>
                <span class="dashboard-tag">Cajas {{ dashboard.summary.open_cash_sessions }}</span>
              </div>

              <p class="muted">
                Accesos directos para continuar con la operacion sin salir del panel principal.
              </p>

              <div class="cta-row">
                <a class="btn btn--primary" [routerLink]="['/sales']">Ventas</a>
                <a class="btn" [routerLink]="['/cash']">Caja</a>
                <a class="btn" [routerLink]="['/reports']">Reportes</a>
              </div>
            </div>
          </article>
        </section>
      }
    </section>
  `,
  styles: `
    .dashboard-page {
      align-content: start;
    }

    .dashboard-hero {
      display: grid;
      gap: 1.5rem;
      grid-template-columns: minmax(0, 1.4fr) minmax(18rem, 0.9fr);
      align-items: stretch;
      padding: 1.6rem;
    }

    .dashboard-hero__copy {
      display: grid;
      gap: 0.65rem;
      align-content: start;
    }

    .dashboard-hero__side {
      display: grid;
      gap: 0.9rem;
      align-content: start;
    }

    .dashboard-balance {
      display: grid;
      gap: 0.28rem;
      border: 1px solid rgba(22, 138, 87, 0.08);
      border-radius: 1.4rem;
      background: rgba(22, 138, 87, 0.04);
      padding: 1rem 1.05rem;
    }

    .dashboard-balance--muted {
      border-color: var(--border);
      background: var(--surface-muted);
    }

    .dashboard-balance small {
      color: var(--text-muted);
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .dashboard-balance strong {
      color: var(--text-soft);
      font-size: 1.45rem;
      line-height: 1.05;
      letter-spacing: -0.03em;
    }

    .dashboard-balance span {
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    .dashboard-message {
      padding: 1.1rem 1.2rem;
    }

    .dashboard-metrics {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(5, minmax(0, 1fr));
    }

    .dashboard-metric {
      display: grid;
      gap: 0.45rem;
      min-height: 9.5rem;
      padding: 1.2rem;
    }

    .dashboard-metric__label {
      color: var(--text-muted);
      font-size: 0.76rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .dashboard-metric__value {
      color: var(--text-soft);
      font-size: clamp(1.45rem, 2vw, 2rem);
      font-weight: 700;
      letter-spacing: -0.04em;
      line-height: 1.05;
    }

    .dashboard-metric__hint {
      color: var(--text-muted);
      font-size: 0.9rem;
      line-height: 1.45;
    }

    .dashboard-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(0, 1.2fr) minmax(20rem, 0.9fr);
    }

    .dashboard-panel {
      display: grid;
      gap: 1rem;
      align-content: start;
      min-height: 100%;
      padding: 1.25rem;
    }

    .dashboard-panel--wide {
      grid-row: span 2;
    }

    .dashboard-panel--soft {
      background: linear-gradient(180deg, rgba(22, 138, 87, 0.04), rgba(255, 255, 255, 0.96));
    }

    .dashboard-panel__header {
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 1rem;
    }

    .dashboard-panel__title {
      margin: 0.2rem 0 0;
      color: var(--text-soft);
      font-size: 1.1rem;
      line-height: 1.2;
    }

    .dashboard-panel__body {
      display: grid;
      gap: 0.9rem;
    }

    .dashboard-list {
      display: grid;
      gap: 0.85rem;
    }

    .dashboard-list--compact {
      gap: 0.75rem;
    }

    .dashboard-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 0.9rem;
      align-items: center;
      border: 1px solid var(--border);
      border-radius: 1.2rem;
      background: var(--surface-muted);
      padding: 0.95rem 1rem;
    }

    .dashboard-row--stacked {
      grid-template-columns: 1fr;
      align-items: start;
    }

    .dashboard-row__copy {
      display: grid;
      gap: 0.2rem;
      min-width: 0;
    }

    .dashboard-row__copy strong {
      color: var(--text-soft);
      font-size: 0.98rem;
    }

    .dashboard-row__copy span,
    .dashboard-row__copy small {
      color: var(--text-muted);
      line-height: 1.45;
    }

    .dashboard-row__amount {
      color: var(--text-soft);
      font-weight: 700;
      white-space: nowrap;
    }

    .dashboard-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.55rem;
    }

    .dashboard-tag {
      display: inline-flex;
      align-items: center;
      min-height: 2rem;
      border: 1px solid var(--border);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.74);
      color: var(--text-soft);
      font-size: 0.82rem;
      font-weight: 600;
      padding: 0 0.78rem;
    }

    .dashboard-tag--accent {
      border-color: rgba(22, 138, 87, 0.14);
      background: rgba(22, 138, 87, 0.08);
      color: var(--primary-strong);
    }

    @media (max-width: 1200px) {
      .dashboard-hero,
      .dashboard-layout {
        grid-template-columns: 1fr;
      }

      .dashboard-metrics {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }

      .dashboard-panel--wide {
        grid-row: auto;
      }
    }

    @media (max-width: 860px) {
      .dashboard-layout {
        grid-template-columns: 1fr;
      }

      .dashboard-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .dashboard-row {
        grid-template-columns: 1fr;
        align-items: start;
      }
    }

    @media (max-width: 640px) {
      .dashboard-metrics {
        grid-template-columns: 1fr;
      }

      .dashboard-hero,
      .dashboard-panel,
      .dashboard-metric {
        padding-left: 1rem;
        padding-right: 1rem;
      }
    }
  `,
})
export class DashboardPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly dashboardApi = inject(DashboardApiService);

  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);
  protected readonly dashboard = signal<DashboardSummary | null>(null);

  private readonly currencyFormatter = new Intl.NumberFormat('es-EC', {
    style: 'currency',
    currency: 'USD',
  });

  private readonly numberFormatter = new Intl.NumberFormat('es-EC', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });

  private readonly dateTimeFormatter = new Intl.DateTimeFormat('es-EC', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });

  public constructor() {
    this.legacyNotice.set(this.resolveLegacyNotice(this.route.snapshot.queryParamMap.get('legacy')));
    void this.load();
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    try {
      this.dashboard.set(await this.dashboardApi.getSummary());
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected formatCurrency(value: number): string {
    return this.currencyFormatter.format(value);
  }

  protected formatNumber(value: number): string {
    return this.numberFormatter.format(value);
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return 'Sin fecha';
    }

    return this.dateTimeFormatter.format(new Date(value));
  }

  protected formatPaymentMethods(methods: string[]): string {
    return methods.length > 0 ? methods.join(', ') : 'Sin pagos';
  }

  protected stockGap(product: DashboardLowStockProduct): number {
    return Math.max(product.minimum_stock - product.current_stock, 0);
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch ((value ?? '').trim()) {
      case 'shell-home':
        return 'La portada legacy fue retirada. Este dashboard ya es el punto de entrada operativo sobre la API nueva.';
      default:
        return null;
    }
  }
}
