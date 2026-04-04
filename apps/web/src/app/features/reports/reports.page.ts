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
import { MatSelectModule } from '@angular/material/select';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { CustomersApiService } from '../customers/customers.api';
import { BusinessPartner } from '../partners/partners.types';
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
    MatSelectModule,
    RouterLink,
  ],
  template: `
    <section class="stack reports-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Reportes</span>
          <h1 class="page-title">Ventas, caja y cobranza sobre el modelo nuevo.</h1>
          <p class="page-description">
            Este modulo ya calcula resumen comercial, utilidad operativa, formas de pago, sesiones
            de caja, tendencias mensuales, detalle de ventas y estado de cuenta de clientes con un
            rango filtrable.
          </p>
        </div>
        <span class="pill">Insights + ventas + cartera + utilidad</span>
      </header>

      @if (legacyNotice()) {
        <article class="surface surface--muted stack">
          <span class="page-kicker">Migracion</span>
          <strong>{{ legacyNotice() }}</strong>
        </article>
      }

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

            <mat-form-field appearance="outline">
              <mat-label>Cliente</mat-label>
              <mat-select formControlName="customer_id">
                <mat-option value="">Todos los clientes</mat-option>
                @for (customer of customers(); track customer.id) {
                  <mat-option [value]="customer.id">
                    {{ customer.name }}
                    @if (customer.document_number) {
                      · {{ customer.document_number }}
                    }
                  </mat-option>
                }
              </mat-select>
            </mat-form-field>

            <div class="cta-row">
              <button mat-flat-button color="primary" type="submit" [disabled]="loading()">
                Aplicar filtro
              </button>
              <button mat-stroked-button type="button" (click)="resetToCurrentMonth()" [disabled]="loading()">
                Mes actual
              </button>
              <button
                mat-stroked-button
                type="button"
                (click)="downloadReceivablesPdf()"
                [disabled]="loading() || exporting()"
              >
                {{ report.receivables.customer ? 'Descargar estado de cuenta' : 'Descargar cartera PDF' }}
              </button>
              <button
                mat-stroked-button
                type="button"
                (click)="downloadProfitabilityPdf()"
                [disabled]="loading() || exporting()"
              >
                Descargar utilidad PDF
              </button>
              <button
                mat-stroked-button
                type="button"
                (click)="downloadSalesPdf()"
                [disabled]="loading() || exporting()"
              >
                Descargar ventas PDF
              </button>
              <button
                mat-stroked-button
                type="button"
                (click)="downloadSalesCsv()"
                [disabled]="loading() || exporting()"
              >
                CSV ventas
              </button>
              <button
                mat-stroked-button
                type="button"
                (click)="downloadProductsCsv()"
                [disabled]="loading() || exporting()"
              >
                CSV productos
              </button>
            </div>
          </form>

          <mat-chip-set>
            <mat-chip>
              Rango {{ formatDate(report.range.date_from) }} a {{ formatDate(report.range.date_to) }}
            </mat-chip>
            <mat-chip>{{ report.summary.sales_count }} venta(s)</mat-chip>
            <mat-chip>{{ report.summary.open_cash_sessions }} caja(s) abierta(s)</mat-chip>
            @if (report.receivables.customer) {
              <mat-chip highlighted>
                Estado de cuenta · {{ report.receivables.customer.name }}
              </mat-chip>
            } @else {
              <mat-chip>{{ report.summary.customers_with_receivables }} cliente(s) con saldo</mat-chip>
            }
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
            <span class="metric-card__label">Cartera pendiente</span>
            <strong class="metric-card__value">
              {{ formatCurrency(report.summary.receivables_total) }}
            </strong>
            <span class="metric-card__hint">Saldo por cobrar despues de pagos y devoluciones.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Ventas con saldo</span>
            <strong class="metric-card__value">{{ report.summary.receivables_sales_count }}</strong>
            <span class="metric-card__hint">Ventas completadas que todavia tienen balance pendiente.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Clientes con saldo</span>
            <strong class="metric-card__value">
              {{ report.summary.customers_with_receivables }}
            </strong>
            <span class="metric-card__hint">Clientes con cuentas por cobrar dentro del rango.</span>
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

        <article class="surface stack">
          <div class="reports-sections">
            <button class="reports-section" type="button" [class.is-active]="reportSection() === 'overview'" (click)="setReportSection('overview')">
              Resumen
            </button>
            <button class="reports-section" type="button" [class.is-active]="reportSection() === 'sales'" (click)="setReportSection('sales')">
              Ventas
            </button>
            <button class="reports-section" type="button" [class.is-active]="reportSection() === 'receivables'" (click)="setReportSection('receivables')">
              Cobranza
            </button>
            <button class="reports-section" type="button" [class.is-active]="reportSection() === 'operations'" (click)="setReportSection('operations')">
              Operacion
            </button>
          </div>
        </article>

        <section class="reports-layout">
          @if (reportSection() === 'overview') {
          <mat-card appearance="outlined" class="reports-card reports-card--wide">
            <mat-card-header>
              <mat-card-title>Utilidad operativa</mat-card-title>
              <mat-card-subtitle>
                Ventas netas, costo vendido y movimientos manuales de caja dentro del rango.
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              <div class="reports-snapshot-grid">
                <article class="reports-snapshot">
                  <span class="reports-snapshot__label">Ventas netas</span>
                  <strong class="reports-snapshot__value">
                    {{ formatCurrency(report.profitability.net_sales_total) }}
                  </strong>
                  <small>Ventas confirmadas menos devoluciones.</small>
                </article>

                <article class="reports-snapshot">
                  <span class="reports-snapshot__label">Devoluciones</span>
                  <strong class="reports-snapshot__value">
                    {{ formatCurrency(report.profitability.refund_total) }}
                  </strong>
                  <small>Reembolsos procesados en el periodo.</small>
                </article>

                <article class="reports-snapshot">
                  <span class="reports-snapshot__label">Costo neto</span>
                  <strong class="reports-snapshot__value">
                    {{ formatCurrency(report.profitability.cost_total) }}
                  </strong>
                  <small>Costo de venta menos costo devuelto.</small>
                </article>

                <article class="reports-snapshot">
                  <span class="reports-snapshot__label">Margen bruto</span>
                  <strong class="reports-snapshot__value">
                    {{ formatCurrency(report.profitability.gross_margin_total) }}
                  </strong>
                  <small>Resultado comercial antes de gastos operativos.</small>
                </article>

                <article class="reports-snapshot">
                  <span class="reports-snapshot__label">Ingresos operativos</span>
                  <strong class="reports-snapshot__value">
                    {{ formatCurrency(report.profitability.operational_income_total) }}
                  </strong>
                  <small>Entradas manuales registradas en caja.</small>
                </article>

                <article class="reports-snapshot">
                  <span class="reports-snapshot__label">Gastos operativos</span>
                  <strong class="reports-snapshot__value">
                    {{ formatCurrency(report.profitability.operational_expenses_total) }}
                  </strong>
                  <small>Salidas manuales que afectan la operacion.</small>
                </article>

                <article class="reports-snapshot reports-snapshot--highlight">
                  <span class="reports-snapshot__label">Utilidad neta</span>
                  <strong class="reports-snapshot__value">
                    {{ formatCurrency(report.profitability.net_utility_total) }}
                  </strong>
                  <small>Margen bruto mas ingresos operativos menos gastos.</small>
                </article>
              </div>
            </mat-card-content>
          </mat-card>

          <mat-card appearance="outlined" class="reports-card">
            <mat-card-header>
              <mat-card-title>Gastos por categoria</mat-card-title>
              <mat-card-subtitle>
                Agrupacion de egresos manuales registrados desde caja.
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.expense_categories.length === 0) {
                <p class="muted">No hay gastos operativos manuales en este rango.</p>
              } @else {
                <div class="reports-bars">
                  @for (expenseCategory of report.expense_categories; track expenseCategory.category) {
                    <article class="reports-bars__item">
                      <div class="reports-bars__header">
                        <strong>{{ formatCategory(expenseCategory.category) }}</strong>
                        <span>{{ formatCurrency(expenseCategory.total) }}</span>
                      </div>
                      <div class="reports-bar reports-bar--danger">
                        <span
                          class="reports-bar__fill reports-bar__fill--danger"
                          [style.width.%]="shareWidth(report.profitability.operational_expenses_total, expenseCategory.total)"
                        ></span>
                      </div>
                      <small>{{ expenseCategory.count }} movimiento(s)</small>
                    </article>
                  }
                </div>
              }
            </mat-card-content>
          </mat-card>

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
          }

          @if (reportSection() === 'sales') {
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
              <mat-card-title>Detalle de ventas</mat-card-title>
              <mat-card-subtitle>
                Resumen comercial por comprobante para reemplazar el reporte legado por rango.
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.sales_documents.length === 0) {
                <p class="muted">No hay ventas completadas en el rango filtrado.</p>
              } @else {
                <div class="reports-cash-list">
                  @for (sale of report.sales_documents; track sale.id) {
                    <article class="reports-cash-item">
                      <div class="reports-cash-item__copy">
                        <strong>
                          {{ sale.document_reference }}
                          @if (sale.invoice_number) {
                            · Factura
                          } @else {
                            · {{ labelForDocument(sale.document_type) }}
                          }
                        </strong>
                        <span>{{ sale.customer_name }} · {{ formatDateTime(sale.sold_at) }}</span>
                        <small>
                          {{ sale.items_count }} item(s) · {{ formatNumber(sale.quantity_total) }}
                          unidad(es) · {{ labelForPaymentStatus(sale.payment_status) }}
                        </small>
                        <small>
                          Cobros
                          {{ formatPaymentMethods(sale.payment_methods) }}
                        </small>
                      </div>
                      <mat-chip-set>
                        <mat-chip>Total {{ formatCurrency(sale.grand_total) }}</mat-chip>
                        <mat-chip>Neto {{ formatCurrency(sale.net_total) }}</mat-chip>
                        <mat-chip>Cobrado {{ formatCurrency(sale.paid_total) }}</mat-chip>
                        @if (sale.returned_total > 0) {
                          <mat-chip>Devuelto {{ formatCurrency(sale.returned_total) }}</mat-chip>
                        }
                        <mat-chip highlighted>Saldo {{ formatCurrency(sale.balance_due) }}</mat-chip>
                      </mat-chip-set>
                    </article>
                  }
                </div>
              }
            </mat-card-content>
          </mat-card>

          <mat-card appearance="outlined" class="reports-card">
            <mat-card-header>
              <mat-card-title>Ventas por mes</mat-card-title>
              <mat-card-subtitle>
                Acumulado mensual del ano {{ report.range.date_to.slice(0, 4) }}.
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.sales_by_month.length === 0) {
                <p class="muted">No hay ventas mensuales para este ano.</p>
              } @else {
                <div class="reports-bars">
                  @for (month of report.sales_by_month; track month.month) {
                    <article class="reports-bars__item">
                      <div class="reports-bars__header">
                        <strong>{{ formatMonthLabel(month.month) }}</strong>
                        <span>{{ formatCurrency(month.total) }}</span>
                      </div>
                      <div class="reports-bar reports-bar--secondary">
                        <span
                          class="reports-bar__fill"
                          [style.width.%]="totalWidth(month.total, report.sales_by_month)"
                        ></span>
                      </div>
                      <small>{{ month.sales_count }} venta(s) en el mes</small>
                    </article>
                  }
                </div>
              }
            </mat-card-content>
          </mat-card>

          <mat-card appearance="outlined" class="reports-card">
            <mat-card-header>
              <mat-card-title>Ultimos 6 meses</mat-card-title>
              <mat-card-subtitle>
                Resumen rodante hasta {{ formatDate(report.range.date_to) }}.
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.sales_last_six_months.length === 0) {
                <p class="muted">No hay ventas registradas en los ultimos 6 meses.</p>
              } @else {
                <div class="reports-bars">
                  @for (month of report.sales_last_six_months; track month.month) {
                    <article class="reports-bars__item">
                      <div class="reports-bars__header">
                        <strong>{{ formatMonthLabel(month.month) }}</strong>
                        <span>{{ formatCurrency(month.total) }}</span>
                      </div>
                      <div class="reports-bar">
                        <span
                          class="reports-bar__fill"
                          [style.width.%]="totalWidth(month.total, report.sales_last_six_months)"
                        ></span>
                      </div>
                      <small>{{ month.sales_count }} venta(s) en el mes</small>
                    </article>
                  }
                </div>
              }
            </mat-card-content>
          </mat-card>
          }

          @if (reportSection() === 'receivables') {
          <mat-card appearance="outlined" class="reports-card reports-card--wide">
            <mat-card-header>
              <mat-card-title>Cartera por cliente</mat-card-title>
              <mat-card-subtitle>
                Saldo agrupado para seguimiento de cobranza y estado de cuenta.
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.receivables.customers.length === 0) {
                <p class="muted">No hay clientes con saldo pendiente en este rango.</p>
              } @else {
                <mat-list>
                  @for (customer of report.receivables.customers; track customer.customer_id ?? customer.name; let last = $last) {
                    <mat-list-item class="reports-list-item">
                      <div class="reports-list-item__content">
                        <strong>{{ customer.name }}</strong>
                        <span>
                          {{ customer.sales_count }} venta(s) con saldo
                          @if (customer.document_number) {
                            · {{ customer.document_number }}
                          }
                        </span>
                        <small>
                          Neto {{ formatCurrency(customer.net_receivable_total) }} · Cobrado
                          {{ formatCurrency(customer.paid_total) }}
                        </small>
                      </div>
                      <div class="reports-list-item__amount">
                        {{ formatCurrency(customer.balance_due) }}
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
              <mat-card-title>Estado de cuenta</mat-card-title>
              <mat-card-subtitle>
                @if (report.receivables.customer) {
                  {{ report.receivables.customer.name }}
                  @if (report.receivables.customer.document_number) {
                    · {{ report.receivables.customer.document_number }}
                  }
                } @else {
                  Ventas pendientes de cobro en el rango filtrado.
                }
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.receivables.sales.length === 0) {
                <p class="muted">No hay ventas con saldo pendiente para este filtro.</p>
              } @else {
                <div class="reports-cash-list">
                  @for (sale of report.receivables.sales; track sale.id) {
                    <article class="reports-cash-item">
                      <div class="reports-cash-item__copy">
                        <strong>
                          {{ sale.public_id || '#' + sale.id }}
                          @if (sale.invoice_number) {
                            · Factura {{ sale.invoice_number }}
                          } @else {
                            · {{ labelForDocument(sale.document_type) }}
                          }
                        </strong>
                        <span>
                          {{ sale.customer_name }} · {{ formatDateTime(sale.sold_at) }}
                        </span>
                        <small>
                          {{ sale.items_count }} item(s) · {{ labelForPaymentStatus(sale.payment_status) }}
                          · Cobros {{ sale.payment_methods.join(', ') || 'Sin registros' }}
                        </small>
                      </div>
                      <mat-chip-set>
                        <mat-chip>Total {{ formatCurrency(sale.grand_total) }}</mat-chip>
                        <mat-chip>Neto {{ formatCurrency(sale.net_receivable_total) }}</mat-chip>
                        <mat-chip>Cobrado {{ formatCurrency(sale.paid_total) }}</mat-chip>
                        <mat-chip highlighted>Saldo {{ formatCurrency(sale.balance_due) }}</mat-chip>
                      </mat-chip-set>
                    </article>
                  }
                </div>
              }
            </mat-card-content>
          </mat-card>
          }

          @if (reportSection() === 'sales') {
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
              <mat-card-title>Ventas por producto</mat-card-title>
              <mat-card-subtitle>
                Detalle consolidado del rango para reemplazar el reporte legado por producto.
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.product_sales.length === 0) {
                <p class="muted">No hay productos vendidos en este rango.</p>
              } @else {
                <mat-list>
                  @for (product of report.product_sales; track product.sku ?? product.name; let last = $last) {
                    <mat-list-item class="reports-list-item">
                      <div class="reports-list-item__content">
                        <strong>{{ product.name }}</strong>
                        <span>
                          @if (product.sku) {
                            SKU {{ product.sku }} ·
                          }
                          {{ product.sales_count }} venta(s) · {{ formatNumber(product.quantity) }} unidad(es)
                        </span>
                        <small>
                          Promedio {{ formatCurrency(product.average_unit_price) }}
                          @if (product.last_sold_at) {
                            · Ultima venta {{ formatDateTime(product.last_sold_at) }}
                          }
                        </small>
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
          }

          @if (reportSection() === 'operations') {
          <mat-card appearance="outlined" class="reports-card">
            <mat-card-header>
              <mat-card-title>Movimientos operativos</mat-card-title>
              <mat-card-subtitle>
                Historial reciente de ingresos y gastos manuales en caja.
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (report.operational_movements.length === 0) {
                <p class="muted">No hay movimientos operativos manuales en este rango.</p>
              } @else {
                <div class="reports-cash-list">
                  @for (movement of report.operational_movements; track movement.id) {
                    <article class="reports-cash-item">
                      <div class="reports-cash-item__copy">
                        <strong>
                          {{ labelForMovementType(movement.type) }}
                          · {{ formatCategory(movement.category) }}
                        </strong>
                        <span>
                          {{ movement.user_name || 'Sin usuario' }}
                          @if (movement.register_name) {
                            · {{ movement.register_name }}
                          }
                          · {{ formatDateTime(movement.occurred_at) }}
                        </span>
                        <small>{{ movement.notes || 'Sin observaciones.' }}</small>
                      </div>
                      <mat-chip-set>
                        <mat-chip>{{ formatCurrency(movement.amount) }}</mat-chip>
                        <mat-chip>{{ labelForMovementType(movement.type) }}</mat-chip>
                      </mat-chip-set>
                    </article>
                  }
                </div>
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
          }
        </section>

        <article class="surface surface--muted stack">
          <span class="page-kicker">Siguiente paso</span>
          <p class="page-description">
            Con este bloque ya podemos seguir ventas, detalle por comprobante, caja, cobranza y
            utilidad operativa desde el stack nuevo, incluyendo descargas PDF y CSV. El siguiente
            frente natural es limpiar el legacy de reportes que ya quedo cubierto.
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

    .reports-sections {
      display: flex;
      gap: 0.65rem;
      flex-wrap: wrap;
    }

    .reports-section {
      border: 1px solid rgba(148, 163, 184, 0.22);
      border-radius: 999px;
      background: rgba(246, 249, 252, 0.95);
      color: var(--text-muted);
      font-weight: 700;
      min-height: 2.8rem;
      padding: 0 1rem;
    }

    .reports-section.is-active {
      border-color: rgba(22, 138, 87, 0.26);
      background: rgba(22, 138, 87, 0.1);
      color: var(--primary-strong);
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

    .reports-snapshot-grid {
      display: grid;
      gap: 0.9rem;
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .reports-snapshot {
      display: grid;
      gap: 0.35rem;
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: rgba(15, 76, 129, 0.03);
      padding: 1rem;
    }

    .reports-snapshot--highlight {
      background:
        linear-gradient(135deg, rgba(15, 76, 129, 0.08), rgba(2, 132, 199, 0.12)),
        rgba(15, 76, 129, 0.04);
      border-color: rgba(15, 76, 129, 0.18);
    }

    .reports-snapshot__label,
    .reports-snapshot small {
      color: var(--text-muted);
    }

    .reports-snapshot__value {
      font-size: 1.2rem;
      line-height: 1.1;
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

    .reports-bar--danger {
      background: rgba(190, 24, 93, 0.12);
    }

    .reports-bar__fill {
      display: block;
      border-radius: inherit;
      background: linear-gradient(90deg, #0f4c81, #2563eb);
      height: 100%;
    }

    .reports-bar__fill--danger {
      background: linear-gradient(90deg, #be185d, #ef4444);
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

      .reports-snapshot-grid {
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
  private readonly route = inject(ActivatedRoute);
  private readonly reportsApi = inject(ReportsApiService);
  private readonly customersApi = inject(CustomersApiService);

  protected readonly loading = signal(false);
  protected readonly downloadingPdf = signal(false);
  protected readonly downloadingProfitabilityPdf = signal(false);
  protected readonly downloadingSalesPdf = signal(false);
  protected readonly downloadingSalesCsv = signal(false);
  protected readonly downloadingProductsCsv = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);
  protected readonly report = signal<ReportsOverview | null>(null);
  protected readonly customers = signal<BusinessPartner[]>([]);
  protected readonly reportSection = signal<'overview' | 'sales' | 'receivables' | 'operations'>('overview');

  protected readonly filtersForm = this.fb.group({
    date_from: [''],
    date_to: [''],
    customer_id: [''],
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

  private readonly monthFormatter = new Intl.DateTimeFormat('es-EC', {
    month: 'short',
    year: 'numeric',
  });

  protected exporting(): boolean {
    return (
      this.downloadingPdf()
      || this.downloadingProfitabilityPdf()
      || this.downloadingSalesPdf()
      || this.downloadingSalesCsv()
      || this.downloadingProductsCsv()
    );
  }

  protected setReportSection(section: 'overview' | 'sales' | 'receivables' | 'operations'): void {
    this.reportSection.set(section);
  }

  public constructor() {
    this.legacyNotice.set(this.resolveLegacyNotice(this.route.snapshot.queryParamMap.get('legacy_source')));
    void this.load(this.initialFiltersFromQuery());
  }

  protected async load(filters: ReportsOverviewFilters = {}): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    try {
      const [report, customers] = await Promise.all([
        this.reportsApi.getOverview(filters),
        this.customersApi.list(),
      ]);

      this.report.set(report);
      this.customers.set(customers.filter((customer) => customer.is_active));
      this.filtersForm.patchValue({
        date_from: report.range.date_from,
        date_to: report.range.date_to,
        customer_id: report.range.customer_id ? String(report.range.customer_id) : '',
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
      customer_id: raw.customer_id ? Number(raw.customer_id) : null,
    });
  }

  protected async resetToCurrentMonth(): Promise<void> {
    const range = this.currentMonthRange();

    this.filtersForm.patchValue({
      date_from: range.date_from ?? '',
      date_to: range.date_to ?? '',
      customer_id: '',
    });
    await this.load(range);
  }

  protected async downloadReceivablesPdf(): Promise<void> {
    const report = this.report();

    if (!report) {
      return;
    }

    this.downloadingPdf.set(true);
    this.error.set(null);

    try {
      const pdf = await this.reportsApi.downloadReceivablesPdf({
        date_from: report.range.date_from,
        date_to: report.range.date_to,
        customer_id: report.range.customer_id,
      });

      this.triggerFileDownload(pdf, this.buildReceivablesPdfFileName(report));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.downloadingPdf.set(false);
    }
  }

  protected async downloadProfitabilityPdf(): Promise<void> {
    const report = this.report();

    if (!report) {
      return;
    }

    this.downloadingProfitabilityPdf.set(true);
    this.error.set(null);

    try {
      const pdf = await this.reportsApi.downloadProfitabilityPdf({
        date_from: report.range.date_from,
        date_to: report.range.date_to,
        customer_id: report.range.customer_id,
      });

      this.triggerFileDownload(pdf, this.buildProfitabilityPdfFileName(report));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.downloadingProfitabilityPdf.set(false);
    }
  }

  protected async downloadSalesPdf(): Promise<void> {
    const report = this.report();

    if (!report) {
      return;
    }

    this.downloadingSalesPdf.set(true);
    this.error.set(null);

    try {
      const pdf = await this.reportsApi.downloadSalesPdf({
        date_from: report.range.date_from,
        date_to: report.range.date_to,
        customer_id: report.range.customer_id,
      });

      this.triggerFileDownload(pdf, this.buildSalesPdfFileName(report));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.downloadingSalesPdf.set(false);
    }
  }

  protected async downloadSalesCsv(): Promise<void> {
    const report = this.report();

    if (!report) {
      return;
    }

    this.downloadingSalesCsv.set(true);
    this.error.set(null);

    try {
      const csv = await this.reportsApi.downloadSalesCsv({
        date_from: report.range.date_from,
        date_to: report.range.date_to,
        customer_id: report.range.customer_id,
      });

      this.triggerFileDownload(csv, this.buildSalesCsvFileName(report));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.downloadingSalesCsv.set(false);
    }
  }

  protected async downloadProductsCsv(): Promise<void> {
    const report = this.report();

    if (!report) {
      return;
    }

    this.downloadingProductsCsv.set(true);
    this.error.set(null);

    try {
      const csv = await this.reportsApi.downloadProductsCsv({
        date_from: report.range.date_from,
        date_to: report.range.date_to,
        customer_id: report.range.customer_id,
      });

      this.triggerFileDownload(csv, this.buildProductsCsvFileName(report));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.downloadingProductsCsv.set(false);
    }
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

  protected formatMonthLabel(value: string): string {
    if (!/^\d{4}-\d{2}$/.test(value)) {
      return value;
    }

    const formatted = this.monthFormatter.format(new Date(`${value}-01T00:00:00`));

    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
  }

  protected labelForMethod(method: string): string {
    switch (method) {
      case 'cash':
        return 'Efectivo';
      case 'card':
        return 'Tarjeta';
      case 'transfer':
        return 'Transferencia';
      case 'check':
        return 'Cheque';
      case 'credit':
        return 'Cuenta por cobrar';
      default:
        return method;
    }
  }

  protected formatPaymentMethods(methods: string[]): string {
    if (methods.length === 0) {
      return 'Sin registros';
    }

    return methods.map((method) => this.labelForMethod(method)).join(', ');
  }

  protected labelForPaymentStatus(status: string): string {
    switch (status) {
      case 'paid':
        return 'Pagada';
      case 'partial':
        return 'Parcial';
      case 'credit':
        return 'Credito';
      default:
        return 'Pendiente';
    }
  }

  protected labelForDocument(value: string | null): string {
    switch (value) {
      case 'factura':
        return 'Factura';
      case 'nota':
        return 'Nota de venta';
      default:
        return 'Ticket';
    }
  }

  protected labelForMovementType(type: string): string {
    switch (type) {
      case 'income':
        return 'Ingreso';
      case 'expense':
        return 'Gasto';
      default:
        return type;
    }
  }

  protected formatCategory(value: string | null): string {
    const normalized = value?.trim() ?? '';

    if (normalized === '') {
      return 'Sin categoria';
    }

    const label = normalized
      .split('_')
      .filter((segment) => segment !== '')
      .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
      .join(' ');

    return label === '' ? 'Sin categoria' : label;
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

  private normalizeCustomerId(value: string | null | undefined): number | null {
    const normalized = value?.trim() ?? '';

    if (normalized === '') {
      return null;
    }

    const parsed = Number(normalized);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
  }

  private initialFiltersFromQuery(): ReportsOverviewFilters {
    const query = this.route.snapshot.queryParamMap;

    return {
      date_from: this.normalizeDate(query.get('date_from')),
      date_to: this.normalizeDate(query.get('date_to')),
      customer_id: this.normalizeCustomerId(query.get('customer_id')),
    };
  }

  private currentMonthRange(): ReportsOverviewFilters {
    const now = new Date();

    return {
      date_from: this.toDateInput(new Date(now.getFullYear(), now.getMonth(), 1)),
      date_to: this.toDateInput(now),
      customer_id: null,
    };
  }

  private toDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  private triggerFileDownload(blob: Blob, fileName: string): void {
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = objectUrl;
    link.download = fileName;
    link.rel = 'noopener';
    link.click();

    window.setTimeout(() => {
      URL.revokeObjectURL(objectUrl);
    }, 1000);
  }

  private buildReceivablesPdfFileName(report: ReportsOverview): string {
    const customer = report.receivables.customer;
    const dateFrom = report.range.date_from;
    const dateTo = report.range.date_to;

    if (customer) {
      return `estado-cuenta-${this.sanitizeFileName(customer.name, 'cliente')}-${dateFrom}-a-${dateTo}.pdf`;
    }

    return `cartera-clientes-${dateFrom}-a-${dateTo}.pdf`;
  }

  private buildProfitabilityPdfFileName(report: ReportsOverview): string {
    return `utilidad-operativa-${report.range.date_from}-a-${report.range.date_to}.pdf`;
  }

  private buildSalesPdfFileName(report: ReportsOverview): string {
    return `ventas-rango-${report.range.date_from}-a-${report.range.date_to}.pdf`;
  }

  private buildSalesCsvFileName(report: ReportsOverview): string {
    return `ventas-rango-${report.range.date_from}-a-${report.range.date_to}.csv`;
  }

  private buildProductsCsvFileName(report: ReportsOverview): string {
    return `ventas-productos-${report.range.date_from}-a-${report.range.date_to}.csv`;
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch ((value ?? '').trim()) {
      case 'reportes':
        return 'El menu general de reportes legacy ahora aterriza en este modulo unificado del stack nuevo.';
      case 'reportes_ventas':
        return 'El antiguo acceso rapido a reportes de ventas fue absorbido por esta vista consolidada.';
      case 'estadistica':
        return 'Las estadisticas legacy ya no viven en pantallas separadas. Aqui se concentran tendencias, cartera y utilidad.';
      case 'reporte_mes':
        return 'El reporte mensual legacy fue traducido al rango correspondiente dentro del modulo nuevo.';
      case 'reporte_anual':
        return 'El reporte anual legacy fue traducido a un rango completo dentro del modulo nuevo.';
      case 'reporte_ultimos_6_meses':
        return 'El reporte de ultimos 6 meses legacy fue absorbido por esta vista y sus tendencias nuevas.';
      default:
        return null;
    }
  }

  private sanitizeFileName(value: string, fallback: string): string {
    const normalized = value
      .trim()
      .replaceAll(/[^A-Za-z0-9._-]+/g, '-')
      .replaceAll(/^[-_.]+|[-_.]+$/g, '');

    return normalized === '' ? fallback : normalized;
  }
}
