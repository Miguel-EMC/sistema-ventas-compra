import { Component, inject, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatDividerModule } from '@angular/material/divider';
import { MatListModule } from '@angular/material/list';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { RouterLink } from '@angular/router';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { DashboardApiService } from './dashboard.api';
import { DashboardLowStockProduct, DashboardSummary } from './dashboard.types';

@Component({
  selector: 'app-dashboard-page',
  imports: [
    MatButtonModule,
    MatCardModule,
    MatChipsModule,
    MatDividerModule,
    MatListModule,
    MatProgressBarModule,
    RouterLink,
  ],
  template: `
    <section class="stack dashboard-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Dashboard</span>
          <h1 class="page-title">Operacion diaria y salud comercial en tiempo real.</h1>
          <p class="page-description">
            Este tablero ya sale de ventas, caja, clientes y stock sobre la API nueva, sin depender
            de calculos del legacy.
          </p>
        </div>
        <span class="pill">Angular Material + insights reales</span>
      </header>

      @if (loading()) {
        <mat-progress-bar mode="indeterminate"></mat-progress-bar>
      }

      @if (error()) {
        <article class="surface surface--muted stack">
          <span class="page-kicker">Operacion</span>
          <strong>{{ error() }}</strong>
        </article>
      }

      @if (dashboard(); as dashboard) {
        <section class="grid grid--cards">
          <article class="surface metric-card">
            <span class="metric-card__label">Ventas de hoy</span>
            <strong class="metric-card__value">
              {{ formatCurrency(dashboard.summary.sales_today_total) }}
            </strong>
            <span class="metric-card__hint">
              {{ dashboard.summary.sales_today_count }} ticket(s) cerrados hoy.
            </span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Ventas del mes</span>
            <strong class="metric-card__value">
              {{ formatCurrency(dashboard.summary.sales_month_total) }}
            </strong>
            <span class="metric-card__hint">
              {{ dashboard.summary.sales_month_count }} venta(s) registradas este mes.
            </span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Clientes activos</span>
            <strong class="metric-card__value">{{ dashboard.summary.active_customers }}</strong>
            <span class="metric-card__hint">Base comercial ya disponible para checkout y reportes.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Cajas abiertas</span>
            <strong class="metric-card__value">{{ dashboard.summary.open_cash_sessions }}</strong>
            <span class="metric-card__hint">Sesiones operativas activas en este momento.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Stock critico</span>
            <strong class="metric-card__value">{{ dashboard.summary.low_stock_products }}</strong>
            <span class="metric-card__hint">Productos por debajo del minimo configurado.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Modulo listo</span>
            <strong class="metric-card__value">POS + Caja</strong>
            <span class="metric-card__hint">El flujo nuevo ya soporta apertura, venta y cierre.</span>
          </article>
        </section>

        <section class="dashboard-layout">
          <mat-card appearance="outlined" class="dashboard-card">
            <mat-card-header>
              <mat-card-title>Ventas recientes</mat-card-title>
              <mat-card-subtitle>Ultimos comprobantes confirmados por la API nueva.</mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (dashboard.recent_sales.length === 0) {
                <p class="muted">Todavia no hay ventas cerradas para mostrar.</p>
              } @else {
                <mat-list>
                  @for (sale of dashboard.recent_sales; track sale.id; let last = $last) {
                    <mat-list-item class="dashboard-list-item">
                      <div class="dashboard-list-item__content">
                        <strong>{{ sale.customer_name || 'Consumidor final' }}</strong>
                        <span>
                          {{ sale.items_count }} item(s)
                          · {{ formatPaymentMethods(sale.payment_methods) }}
                        </span>
                        <small>
                          {{ sale.register_name || 'Sin caja' }}
                          · {{ formatDateTime(sale.sold_at) }}
                        </small>
                      </div>
                      <div class="dashboard-list-item__amount">
                        {{ formatCurrency(sale.grand_total) }}
                      </div>
                    </mat-list-item>
                    @if (!last) {
                      <mat-divider></mat-divider>
                    }
                  }
                </mat-list>
              }
            </mat-card-content>
          </mat-card>

          <mat-card appearance="outlined" class="dashboard-card">
            <mat-card-header>
              <mat-card-title>Alertas de stock</mat-card-title>
              <mat-card-subtitle>Productos que ya necesitan reposicion o ajuste.</mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (dashboard.low_stock_products.length === 0) {
                <p class="muted">No hay productos en estado critico.</p>
              } @else {
                <div class="dashboard-stock-list">
                  @for (product of dashboard.low_stock_products; track product.id) {
                    <article class="dashboard-stock-item">
                      <div class="dashboard-stock-item__copy">
                        <strong>{{ product.name }}</strong>
                        <small>
                          Faltan {{ formatNumber(stockGap(product)) }} {{ product.unit }} para volver
                          al minimo.
                        </small>
                      </div>
                      <mat-chip-set>
                        <mat-chip>Actual {{ formatNumber(product.current_stock) }}</mat-chip>
                        <mat-chip highlighted>Minimo {{ formatNumber(product.minimum_stock) }}</mat-chip>
                      </mat-chip-set>
                    </article>
                  }
                </div>
              }
            </mat-card-content>
          </mat-card>

          <mat-card appearance="outlined" class="dashboard-card dashboard-card--accent">
            <mat-card-header>
              <mat-card-title>Acciones rapidas</mat-card-title>
              <mat-card-subtitle>Ruta corta para seguir migrando la operacion diaria.</mat-card-subtitle>
            </mat-card-header>

            <mat-card-content class="stack">
              <mat-chip-set>
                <mat-chip>Hoy {{ dashboard.summary.sales_today_count }} venta(s)</mat-chip>
                <mat-chip>Mes {{ dashboard.summary.sales_month_count }} ticket(s)</mat-chip>
                <mat-chip>Cajas {{ dashboard.summary.open_cash_sessions }}</mat-chip>
              </mat-chip-set>

              <p class="muted">
                La nueva base ya puede operar el ciclo diario completo. Desde aqui conviene seguir
                con ventas, revisar caja o entrar a reportes para auditar resultados.
              </p>

              <div class="cta-row">
                <a mat-flat-button color="primary" [routerLink]="['/sales']">Ir al POS</a>
                <a mat-stroked-button [routerLink]="['/cash']">Revisar caja</a>
                <a mat-stroked-button [routerLink]="['/reports']">Ver reportes</a>
              </div>
            </mat-card-content>
          </mat-card>
        </section>
      }
    </section>
  `,
  styles: `
    .dashboard-page {
      align-content: start;
    }

    .dashboard-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr) minmax(18rem, 0.9fr);
    }

    .dashboard-card {
      border-radius: 1.5rem;
      height: 100%;
    }

    .dashboard-card mat-card-content:first-of-type {
      margin-top: 1rem;
    }

    .dashboard-card--accent {
      background:
        linear-gradient(180deg, rgba(15, 76, 129, 0.08), rgba(255, 255, 255, 0.98)),
        var(--surface);
    }

    .dashboard-list-item {
      height: auto;
      align-items: start;
      padding-block: 0.55rem;
    }

    .dashboard-list-item__content {
      display: grid;
      gap: 0.2rem;
      min-width: 0;
      padding-right: 1rem;
    }

    .dashboard-list-item__content span,
    .dashboard-list-item__content small {
      color: var(--text-muted);
    }

    .dashboard-list-item__amount {
      font-weight: 700;
      white-space: nowrap;
    }

    .dashboard-stock-list {
      display: grid;
      gap: 0.85rem;
    }

    .dashboard-stock-item {
      display: grid;
      gap: 0.75rem;
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: rgba(15, 76, 129, 0.03);
      padding: 1rem;
    }

    .dashboard-stock-item__copy {
      display: grid;
      gap: 0.2rem;
    }

    .dashboard-stock-item__copy small {
      color: var(--text-muted);
    }

    @media (max-width: 1200px) {
      .dashboard-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .dashboard-card--accent {
        grid-column: 1 / -1;
      }
    }

    @media (max-width: 860px) {
      .dashboard-layout {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class DashboardPageComponent {
  private readonly dashboardApi = inject(DashboardApiService);

  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);
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
}
