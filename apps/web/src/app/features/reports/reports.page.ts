import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatDividerModule } from '@angular/material/divider';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatListModule } from '@angular/material/list';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { RouterLink } from '@angular/router';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { ReportsApiService } from './reports.api';
import { ReportsOverview, ReportsOverviewFilters } from './reports.types';

@Component({
  selector: 'app-reports-page',
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatCardModule,
    MatChipsModule,
    MatDividerModule,
    MatFormFieldModule,
    MatInputModule,
    MatListModule,
    MatProgressBarModule,
    RouterLink,
  ],
  template: `
    <section class="stack reports-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Reportes</span>
          <h1 class="page-title">Ventas, caja y rotacion sobre el modelo nuevo.</h1>
          <p class="page-description">
            Este modulo ya calcula resumen comercial, formas de pago, ventas por dia, top de
            productos y sesiones de caja con un rango filtrable.
          </p>
        </div>
        <span class="pill">Insights operativos</span>
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

      @if (report(); as report) {
        <article class="surface stack">
          <form class="reports-filter" [formGroup]="filtersForm" (ngSubmit)="applyFilters()">
            <mat-form-field appearance="outline">
              <mat-label>Desde</mat-label>
              <input matInput type="date" formControlName="date_from" />
            </mat-form-field>

            <mat-form-field appearance="outline">
              <mat-label>Hasta</mat-label>
              <input matInput type="date" formControlName="date_to" />
            </mat-form-field>

            <div class="cta-row">
              <button mat-flat-button color="primary" type="submit" [disabled]="loading()">
                Aplicar filtro
              </button>
              <button mat-stroked-button type="button" (click)="resetToCurrentMonth()" [disabled]="loading()">
                Mes actual
              </button>
            </div>
          </form>

          <mat-chip-set>
            <mat-chip>
              Rango {{ formatDate(report.range.date_from) }} a {{ formatDate(report.range.date_to) }}
            </mat-chip>
            <mat-chip>{{ report.summary.sales_count }} venta(s)</mat-chip>
            <mat-chip>{{ report.summary.open_cash_sessions }} caja(s) abierta(s)</mat-chip>
          </mat-chip-set>
        </article>

        <section class="grid grid--cards">
          <article class="surface metric-card">
            <span class="metric-card__label">Ventas del rango</span>
            <strong class="metric-card__value">{{ formatCurrency(report.summary.sales_total) }}</strong>
            <span class="metric-card__hint">Facturacion confirmada entre las fechas elegidas.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Ticket promedio</span>
            <strong class="metric-card__value">
              {{ formatCurrency(report.summary.average_ticket) }}
            </strong>
            <span class="metric-card__hint">Promedio por comprobante dentro del rango.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Ventas en efectivo</span>
            <strong class="metric-card__value">
              {{ formatCurrency(report.summary.cash_sales_total) }}
            </strong>
            <span class="metric-card__hint">Cobros registrados con metodo cash.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Clientes activos</span>
            <strong class="metric-card__value">{{ report.summary.customers_total }}</strong>
            <span class="metric-card__hint">Clientes disponibles en la base comercial nueva.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Stock critico</span>
            <strong class="metric-card__value">{{ report.summary.low_stock_products }}</strong>
            <span class="metric-card__hint">SKU(s) por debajo del minimo de reposicion.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Caja activa</span>
            <strong class="metric-card__value">{{ report.summary.open_cash_sessions }}</strong>
            <span class="metric-card__hint">Sesiones de caja abiertas al momento del reporte.</span>
          </article>
        </section>

        <section class="reports-layout">
          <mat-card appearance="outlined" class="reports-card">
            <mat-card-header>
              <mat-card-title>Formas de pago</mat-card-title>
              <mat-card-subtitle>Distribucion de cobros en el rango filtrado.</mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.payment_methods.length === 0) {
                <p class="muted">No hay pagos registrados en este rango.</p>
              } @else {
                <div class="reports-bars">
                  @for (paymentMethod of report.payment_methods; track paymentMethod.method) {
                    <article class="reports-bars__item">
                      <div class="reports-bars__header">
                        <strong>{{ labelForMethod(paymentMethod.method) }}</strong>
                        <span>{{ formatCurrency(paymentMethod.total) }}</span>
                      </div>
                      <div class="reports-bar">
                        <span
                          class="reports-bar__fill"
                          [style.width.%]="shareWidth(report.summary.sales_total, paymentMethod.total)"
                        ></span>
                      </div>
                      <small>{{ paymentMethod.count }} pago(s) asociados</small>
                    </article>
                  }
                </div>
              }
            </mat-card-content>
          </mat-card>

          <mat-card appearance="outlined" class="reports-card">
            <mat-card-header>
              <mat-card-title>Ventas por dia</mat-card-title>
              <mat-card-subtitle>Tendencia diaria del periodo consultado.</mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.sales_by_day.length === 0) {
                <p class="muted">No hay ventas para graficar en este rango.</p>
              } @else {
                <div class="reports-bars">
                  @for (day of report.sales_by_day; track day.day) {
                    <article class="reports-bars__item">
                      <div class="reports-bars__header">
                        <strong>{{ formatDate(day.day) }}</strong>
                        <span>{{ formatCurrency(day.total) }}</span>
                      </div>
                      <div class="reports-bar reports-bar--secondary">
                        <span
                          class="reports-bar__fill"
                          [style.width.%]="totalWidth(day.total, report.sales_by_day)"
                        ></span>
                      </div>
                      <small>{{ day.sales_count }} venta(s) ese dia</small>
                    </article>
                  }
                </div>
              }
            </mat-card-content>
          </mat-card>

          <mat-card appearance="outlined" class="reports-card reports-card--wide">
            <mat-card-header>
              <mat-card-title>Top productos</mat-card-title>
              <mat-card-subtitle>Los items con mayor rotacion e ingreso en el rango.</mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.top_products.length === 0) {
                <p class="muted">No hay productos vendidos en el periodo elegido.</p>
              } @else {
                <mat-list>
                  @for (product of report.top_products; track product.name; let last = $last) {
                    <mat-list-item class="reports-list-item">
                      <div class="reports-list-item__content">
                        <strong>{{ product.name }}</strong>
                        <span>{{ formatNumber(product.quantity) }} unidad(es) vendidas</span>
                      </div>
                      <div class="reports-list-item__amount">
                        {{ formatCurrency(product.total) }}
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

          <mat-card appearance="outlined" class="reports-card reports-card--wide">
            <mat-card-header>
              <mat-card-title>Sesiones de caja</mat-card-title>
              <mat-card-subtitle>Resumen de aperturas y cierres dentro del rango.</mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.cash_sessions.length === 0) {
                <p class="muted">No hay sesiones de caja en el periodo consultado.</p>
              } @else {
                <div class="reports-cash-list">
                  @for (session of report.cash_sessions; track session.id) {
                    <article class="reports-cash-item">
                      <div class="reports-cash-item__copy">
                        <strong>{{ session.register_name || 'Caja' }}</strong>
                        <span>
                          {{ session.opened_by_name || 'Sin usuario' }}
                          · {{ session.status === 'open' ? 'Abierta' : 'Cerrada' }}
                        </span>
                        <small>
                          Apertura {{ formatDateTime(session.opened_at) }}
                          @if (session.closed_at) {
                            · Cierre {{ formatDateTime(session.closed_at) }}
                          }
                        </small>
                      </div>
                      <mat-chip-set>
                        <mat-chip>{{ session.sales_count }} venta(s)</mat-chip>
                        <mat-chip>Ventas {{ formatCurrency(session.sales_total) }}</mat-chip>
                        <mat-chip highlighted>Balance {{ formatCurrency(session.cash_balance) }}</mat-chip>
                      </mat-chip-set>
                    </article>
                  }
                </div>
              }
            </mat-card-content>
          </mat-card>
        </section>

        <article class="surface surface--muted stack">
          <span class="page-kicker">Siguiente paso</span>
          <p class="page-description">
            Con este bloque ya podemos auditar ventas y caja sobre el stack nuevo. El siguiente
            frente natural es sacar Settings del legacy y empezar compras/abastecimiento.
          </p>
          <div class="cta-row">
            <a mat-stroked-button [routerLink]="['/sales']">Revisar POS</a>
            <a mat-stroked-button [routerLink]="['/cash']">Abrir/Cerrar caja</a>
          </div>
        </article>
      }
    </section>
  `,
  styles: `
    .reports-page {
      align-content: start;
    }

    .reports-filter {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
    }

    .reports-filter mat-form-field {
      min-width: min(15rem, 100%);
    }

    .reports-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reports-card {
      border-radius: 1.5rem;
      height: 100%;
    }

    .reports-card mat-card-content:first-of-type {
      margin-top: 1rem;
    }

    .reports-card--wide {
      grid-column: 1 / -1;
    }

    .reports-bars,
    .reports-cash-list {
      display: grid;
      gap: 0.9rem;
    }

    .reports-bars__item,
    .reports-cash-item {
      display: grid;
      gap: 0.45rem;
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: rgba(15, 76, 129, 0.03);
      padding: 1rem;
    }

    .reports-bars__header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }

    .reports-bars__header span,
    .reports-bars__item small,
    .reports-cash-item__copy span,
    .reports-cash-item__copy small {
      color: var(--text-muted);
    }

    .reports-bar {
      overflow: hidden;
      border-radius: 999px;
      background: rgba(15, 76, 129, 0.1);
      height: 0.7rem;
    }

    .reports-bar--secondary {
      background: rgba(2, 132, 199, 0.12);
    }

    .reports-bar__fill {
      display: block;
      border-radius: inherit;
      background: linear-gradient(90deg, #0f4c81, #2563eb);
      height: 100%;
    }

    .reports-list-item {
      height: auto;
      align-items: start;
      padding-block: 0.55rem;
    }

    .reports-list-item__content,
    .reports-cash-item__copy {
      display: grid;
      gap: 0.2rem;
      min-width: 0;
    }

    .reports-list-item__content span {
      color: var(--text-muted);
    }

    .reports-list-item__amount {
      font-weight: 700;
      white-space: nowrap;
    }

    @media (max-width: 960px) {
      .reports-layout {
        grid-template-columns: 1fr;
      }

      .reports-card--wide {
        grid-column: auto;
      }
    }
  `,
})
export class ReportsPageComponent {
  private readonly fb = inject(FormBuilder);
  private readonly reportsApi = inject(ReportsApiService);

  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly report = signal<ReportsOverview | null>(null);

  protected readonly filtersForm = this.fb.group({
    date_from: [''],
    date_to: [''],
  });

  private readonly currencyFormatter = new Intl.NumberFormat('es-EC', {
    style: 'currency',
    currency: 'USD',
  });

  private readonly numberFormatter = new Intl.NumberFormat('es-EC', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });

  private readonly dateFormatter = new Intl.DateTimeFormat('es-EC', {
    dateStyle: 'medium',
  });

  private readonly dateTimeFormatter = new Intl.DateTimeFormat('es-EC', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });

  public constructor() {
    void this.load();
  }

  protected async load(filters: ReportsOverviewFilters = {}): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    try {
      const report = await this.reportsApi.getOverview(filters);
      this.report.set(report);
      this.filtersForm.patchValue({
        date_from: report.range.date_from,
        date_to: report.range.date_to,
      });
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected async applyFilters(): Promise<void> {
    const raw = this.filtersForm.getRawValue();

    await this.load({
      date_from: this.normalizeDate(raw.date_from),
      date_to: this.normalizeDate(raw.date_to),
    });
  }

  protected async resetToCurrentMonth(): Promise<void> {
    const range = this.currentMonthRange();

    this.filtersForm.patchValue(range);
    await this.load(range);
  }

  protected formatCurrency(value: number): string {
    return this.currencyFormatter.format(value);
  }

  protected formatNumber(value: number): string {
    return this.numberFormatter.format(value);
  }

  protected formatDate(value: string | null): string {
    if (!value) {
      return 'Sin fecha';
    }

    return this.dateFormatter.format(new Date(`${value}T00:00:00`));
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return 'Sin fecha';
    }

    return this.dateTimeFormatter.format(new Date(value));
  }

  protected labelForMethod(method: string): string {
    switch (method) {
      case 'cash':
        return 'Efectivo';
      case 'card':
        return 'Tarjeta';
      case 'transfer':
        return 'Transferencia';
      default:
        return method;
    }
  }

  protected shareWidth(total: number, segment: number): number {
    if (total <= 0 || segment <= 0) {
      return 0;
    }

    return Math.max(10, Math.round((segment / total) * 100));
  }

  protected totalWidth(value: number, collection: Array<{ total: number }>): number {
    const max = collection.reduce((currentMax, item) => Math.max(currentMax, item.total), 0);

    if (max <= 0 || value <= 0) {
      return 0;
    }

    return Math.max(10, Math.round((value / max) * 100));
  }

  private normalizeDate(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }

  private currentMonthRange(): { date_from: string; date_to: string } {
    const now = new Date();

    return {
      date_from: this.toDateInput(new Date(now.getFullYear(), now.getMonth(), 1)),
      date_to: this.toDateInput(now),
    };
  }

  private toDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }
}
