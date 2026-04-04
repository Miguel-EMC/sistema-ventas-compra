import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
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
import { AuthService } from '../../core/auth/auth.service';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { CustomersApiService } from '../customers/customers.api';
import { BusinessPartner } from '../partners/partners.types';
import { ProductsApiService } from '../products/products.api';
import { Product } from '../products/products.types';
import { SettingsApiService } from '../settings/settings.api';
import { BusinessSettings } from '../settings/settings.types';
import { SalesApiService } from './sales.api';
import {
  CheckoutSalePayload,
  CreateSalePaymentPayload,
  CreditNoteRecord,
  CreateSaleReturnPayload,
  InvoiceRecord,
  SaleDetail,
  SaleDraft,
  SaleDraftItem,
  SaleItemRecord,
  SaleRecord,
  SaleReturnRecord,
  UpdateSaleDraftPayload,
} from './sales.types';

@Component({
  selector: 'app-sales-page',
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
    <section class="stack sales-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">POS y checkout</span>
          <h1 class="page-title">Ventas, cobro y facturacion en una sola operacion.</h1>
          <p class="page-description">
            Gestiona la venta desde una interfaz mas clara: prepara el pedido, confirma el cobro y
            revisa el historial posterior sin mezclar demasiadas tareas en la misma vista.
          </p>
        </div>
        <span class="pill">Operacion comercial</span>
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

      @if (successMessage()) {
        <article class="surface stack sales-success">
          <span class="page-kicker">Ultima operacion</span>
          <strong>{{ successMessage() }}</strong>
        </article>
      }

      @if (legacyNotice()) {
        <article class="surface surface--muted stack sales-warning">
          <span class="page-kicker">Migracion</span>
          <strong>{{ legacyNotice() }}</strong>
        </article>
      }

      @if (!activeTaxResolution()) {
        <article class="surface surface--muted stack sales-warning">
          <span class="page-kicker">Facturacion</span>
          <p class="page-description">
            No hay una dosificacion activa. Puedes seguir vendiendo con ticket o nota, pero la
            factura queda bloqueada hasta configurarla en Settings.
          </p>
          <div class="cta-row">
            <a mat-stroked-button [routerLink]="['/settings']">Ir a Settings</a>
          </div>
        </article>
      } @else {
        <article class="surface stack">
          <span class="page-kicker">Dosificacion activa</span>
          <strong>
            {{ activeTaxResolution()?.series || 'Sin serie' }} ·
            {{ activeTaxResolution()?.authorization_number }}
          </strong>
          <span class="muted">
            Siguiente {{ formatSequence(activeTaxResolution()?.next_invoice_number ?? 0) }} ·
            Restantes {{ activeTaxResolution()?.remaining_invoices ?? 0 }}
          </span>
        </article>
      }

      @if (!draft()?.cash_session) {
        <article class="surface surface--muted stack">
          <span class="page-kicker">Caja requerida</span>
          <p class="page-description">
            El POS ya requiere una caja abierta para confirmar ventas. Abre una sesión primero y
            luego vuelve a este borrador.
          </p>
          <div class="cta-row">
            <a mat-flat-button color="primary" [routerLink]="['/cash']">Ir a Caja</a>
          </div>
        </article>
      } @else {
        <article class="surface stack">
          <span class="page-kicker">Sesion activa</span>
          <strong>
            {{ draft()?.cash_session?.register_name || 'Caja activa' }}
            · abierta {{ formatDateTime(draft()?.cash_session?.opened_at ?? null) }}
          </strong>
        </article>
      }

      @if (invoiceBlockReason()) {
        <article class="surface surface--muted stack sales-warning">
          <span class="page-kicker">Factura bloqueada</span>
          <strong>{{ invoiceBlockReason() }}</strong>
        </article>
      }

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Items en borrador</span>
          <strong class="metric-card__value">{{ draft()?.total_items ?? 0 }}</strong>
          <span class="metric-card__hint">El carrito actual ya esta aislado por usuario.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Total actual</span>
          <strong class="metric-card__value">{{ formatCurrency(draft()?.grand_total ?? 0) }}</strong>
          <span class="metric-card__hint">Subtotal + impuesto calculado desde los items.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Productos disponibles</span>
          <strong class="metric-card__value">{{ products().length }}</strong>
          <span class="metric-card__hint">Catalogo activo listo para agregar al POS.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Ventas recientes</span>
          <strong class="metric-card__value">{{ recentSales().length }}</strong>
          <span class="metric-card__hint">Ultimas transacciones confirmadas en la API.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Modo documento</span>
          <strong class="metric-card__value">{{ labelForDocument(selectedDocumentType()) }}</strong>
          <span class="metric-card__hint">
            {{
              selectedDocumentType() === 'factura'
                ? 'Emitira comprobante fiscal.'
                : 'Venta operativa sin factura.'
            }}
          </span>
        </article>
      </section>

      <article class="surface stack">
        <div class="sales-sections">
          <button class="sales-section" type="button" [class.is-active]="salesView() === 'checkout'" (click)="setSalesView('checkout')">
            Operar POS
          </button>
          <button class="sales-section" type="button" [class.is-active]="salesView() === 'history'" (click)="setSalesView('history')">
            Historial y postventa
          </button>
        </div>
      </article>

      <section class="sales-layout">
        @if (salesView() === 'checkout') {
        <mat-card appearance="outlined" class="sales-card">
          <mat-card-header>
            <mat-card-title>Datos de la venta</mat-card-title>
            <mat-card-subtitle>Cliente, observaciones y carga inicial del pedido.</mat-card-subtitle>
          </mat-card-header>

          <mat-card-content class="stack">
            <form class="sales-form" [formGroup]="draftForm">
              <mat-form-field appearance="outline">
                <mat-label>Cliente</mat-label>
                <mat-select formControlName="customer_id">
                  <mat-option value="">Consumidor final</mat-option>
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

              <mat-form-field appearance="outline">
                <mat-label>Notas</mat-label>
                <textarea matInput rows="3" formControlName="notes"></textarea>
              </mat-form-field>

              <div class="cta-row">
                <button mat-stroked-button type="button" (click)="saveDraftMeta()">
                  Guardar cabecera
                </button>
              </div>
            </form>

            <mat-divider></mat-divider>

            <form class="sales-form" [formGroup]="itemForm" (ngSubmit)="addItem()">
              <mat-form-field appearance="outline">
                <mat-label>Producto</mat-label>
                <mat-select
                  formControlName="product_id"
                  (selectionChange)="onProductSelectionChange($event.value)"
                >
                  @for (product of products(); track product.id) {
                    <mat-option [value]="product.id">
                      {{ product.name }} · {{ formatCurrency(product.sale_price) }}
                      · Stock {{ formatNumber(product.current_stock) }}
                    </mat-option>
                  }
                </mat-select>
              </mat-form-field>

              <mat-form-field appearance="outline">
                <mat-label>Cantidad</mat-label>
                <input matInput type="number" min="0.01" step="0.01" formControlName="quantity" />
              </mat-form-field>

              @if (selectedProduct(); as product) {
                <mat-chip-set>
                  <mat-chip>Precio {{ formatCurrency(product.sale_price) }}</mat-chip>
                  <mat-chip>Unidad {{ product.unit }}</mat-chip>
                  <mat-chip>Stock {{ formatNumber(product.current_stock) }}</mat-chip>
                </mat-chip-set>
              }

              <div class="cta-row">
                <button
                  mat-flat-button
                  color="primary"
                  type="submit"
                  [disabled]="addingItem() || products().length === 0 || !draft()?.cash_session"
                >
                  Agregar al borrador
                </button>
              </div>
            </form>
          </mat-card-content>
        </mat-card>

        <mat-card appearance="outlined" class="sales-card sales-card--draft">
          <mat-card-header>
            <mat-card-title>Resumen actual</mat-card-title>
            <mat-card-subtitle>
              {{ draft()?.customer?.name || 'Consumidor final' }}
            </mat-card-subtitle>
          </mat-card-header>

          <mat-card-content class="stack">
            <mat-chip-set>
              <mat-chip>Subtotal {{ formatCurrency(draft()?.subtotal ?? 0) }}</mat-chip>
              <mat-chip>Impuesto {{ formatCurrency(draft()?.tax_total ?? 0) }}</mat-chip>
              <mat-chip highlighted>Total {{ formatCurrency(draft()?.grand_total ?? 0) }}</mat-chip>
              @if (draft()?.customer?.document_number) {
                <mat-chip>Doc {{ draft()?.customer?.document_number }}</mat-chip>
              }
            </mat-chip-set>

            @if (!draft() || draft()?.items?.length === 0) {
              <p class="muted">
                El borrador esta vacio. Agrega productos desde el panel izquierdo para preparar el
                checkout.
              </p>
            } @else {
              <div class="draft-items">
                @for (item of draft()?.items ?? []; track item.id) {
                  <article class="draft-item">
                    <div class="draft-item__copy">
                      <strong>{{ item.name_snapshot }}</strong>
                      <small>
                        {{ formatCurrency(item.unit_price) }} por unidad
                        @if (item.product) {
                          · Stock {{ formatNumber(item.product.current_stock) }}
                        }
                      </small>
                      <mat-chip-set>
                        <mat-chip>Cantidad {{ formatNumber(item.quantity) }}</mat-chip>
                        <mat-chip>Total {{ formatCurrency(item.line_total) }}</mat-chip>
                        @if (item.product?.track_stock && (item.product?.current_stock ?? 0) < item.quantity) {
                          <mat-chip class="sales-chip-warning">Stock insuficiente</mat-chip>
                        }
                      </mat-chip-set>
                    </div>

                    <div class="draft-item__actions">
                      <button
                        mat-icon-button
                        type="button"
                        aria-label="Reducir cantidad"
                        (click)="decreaseQuantity(item)"
                      >
                        <span aria-hidden="true">-</span>
                      </button>
                      <button
                        mat-icon-button
                        type="button"
                        aria-label="Aumentar cantidad"
                        (click)="increaseQuantity(item)"
                      >
                        <span aria-hidden="true">+</span>
                      </button>
                      <button
                        mat-icon-button
                        type="button"
                        aria-label="Eliminar item"
                        (click)="removeItem(item)"
                      >
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>
                  </article>
                }
              </div>
            }
          </mat-card-content>

          <mat-divider></mat-divider>

          <mat-card-content class="stack">
            <form class="sales-form sales-form--checkout" [formGroup]="checkoutForm" (ngSubmit)="checkout()">
              <mat-form-field appearance="outline">
                <mat-label>Documento</mat-label>
                <mat-select
                  formControlName="document_type"
                  (selectionChange)="onDocumentTypeChange($event.value)"
                >
                  @for (option of documentTypes; track option.value) {
                    <mat-option [value]="option.value">{{ option.label }}</mat-option>
                  }
                </mat-select>
              </mat-form-field>

              <mat-form-field appearance="outline">
                <mat-label>Metodo de pago</mat-label>
                <mat-select
                  formControlName="payment_method"
                  (selectionChange)="onCheckoutPaymentMethodChange($event.value)"
                >
                  @for (option of paymentMethods; track option.value) {
                    <mat-option [value]="option.value">{{ option.label }}</mat-option>
                  }
                </mat-select>
              </mat-form-field>

              <mat-form-field appearance="outline">
                <mat-label>Monto pagado</mat-label>
                <input matInput type="number" min="0" step="0.01" formControlName="amount_paid" />
              </mat-form-field>

              <mat-form-field appearance="outline">
                <mat-label>Referencia</mat-label>
                <input matInput type="text" formControlName="reference" />
              </mat-form-field>

              <div class="sales-payment-meta">
                <mat-chip-set>
                  <mat-chip>Cobro inicial {{ formatCurrency(checkoutAppliedAmount()) }}</mat-chip>
                  <mat-chip>Saldo {{ formatCurrency(checkoutBalanceDue()) }}</mat-chip>
                  @if (checkoutChangeTotal() > 0) {
                    <mat-chip highlighted>Vuelto {{ formatCurrency(checkoutChangeTotal()) }}</mat-chip>
                  }
                </mat-chip-set>

                @if (checkoutForm.getRawValue().payment_method === 'credit') {
                  <p class="muted sales-warning-text">
                    Esta venta se confirmara con saldo pendiente para registrar abonos despues.
                  </p>
                } @else if (checkoutBalanceDue() > 0) {
                  <p class="muted sales-warning-text">
                    El checkout quedara parcial y la venta seguira visible con saldo por cobrar.
                  </p>
                }
              </div>

              @if (selectedDocumentType() === 'factura') {
                <div class="sales-invoice-meta">
                  <mat-chip-set>
                    <mat-chip>
                      Resolucion
                      {{ activeTaxResolution()?.series || 'No disponible' }}
                    </mat-chip>
                    <mat-chip>
                      Cliente
                      {{ draft()?.customer?.document_number || 'Documento requerido' }}
                    </mat-chip>
                    @if (businessSettings()?.system_settings?.invoice_footer) {
                      <mat-chip>Pie configurado</mat-chip>
                    }
                  </mat-chip-set>

                  <p class="muted sales-warning-text">
                    La factura se genera en la misma transaccion de la venta y consume el siguiente
                    numero disponible de la dosificacion activa.
                  </p>
                </div>
              }

              <div class="cta-row">
                <button
                  mat-flat-button
                  color="primary"
                  type="submit"
                  [disabled]="isCheckoutDisabled()"
                >
                  Confirmar venta
                </button>
              </div>
            </form>
          </mat-card-content>
        </mat-card>
        }

        @if (salesView() === 'history') {
        <mat-card appearance="outlined" class="sales-card">
          <mat-card-header>
            <mat-card-title>Ventas recientes</mat-card-title>
            <mat-card-subtitle>
              Ultimas transacciones confirmadas por la API, anulaciones y devoluciones parciales
              operadas desde el stack nuevo.
            </mat-card-subtitle>
          </mat-card-header>

          <mat-card-content>
            @if (recentSales().length === 0) {
              <p class="muted">Todavia no hay ventas confirmadas en esta base nueva.</p>
            } @else {
              <mat-list>
                @for (sale of recentSales(); track sale.id) {
                  <mat-list-item class="sales-history-item">
                    <div class="sales-history-item__content">
                      <strong>{{ sale.customer?.name || 'Consumidor final' }}</strong>
                      <span>
                        {{ sale.items_count }} item(s) · {{ sale.payment_methods.join(', ') || 'Sin pago' }}
                      </span>
                      <small>
                        {{ describeSaleDocument(sale) }} · {{ formatSaleTimeline(sale) }}
                      </small>
                      <small>
                        Cobro {{ labelForPaymentStatus(sale.payment_status) }} · Saldo
                        {{ formatCurrency(sale.balance_due) }}
                      </small>
                      @if (sale.returns_count > 0) {
                        <small>
                          Devuelto {{ formatCurrency(sale.returned_total) }} en
                          {{ sale.returns_count }} movimiento(s)
                        </small>
                      }
                    </div>
                    <div class="sales-history-item__actions">
                      <div class="sales-history-item__amount">
                        {{ formatCurrency(sale.grand_total) }}
                      </div>
                      <div class="sales-history-item__toolbar">
                        <button
                          mat-stroked-button
                          type="button"
                          (click)="toggleSaleDetail(sale)"
                          [disabled]="loadingSaleDetail() && selectedSaleId() === sale.id"
                        >
                          {{ selectedSaleId() === sale.id ? 'Ocultar' : 'Detalle' }}
                        </button>
                        @if (auth.isAdmin() && sale.status === 'completed') {
                          <button
                            mat-stroked-button
                            type="button"
                            (click)="prepareSaleCancellation(sale)"
                            [disabled]="cancellingSaleId() === sale.id"
                          >
                            Anular
                          </button>
                        }
                      </div>
                      @if (sale.status === 'cancelled') {
                        <span class="sales-history-item__badge">Anulada</span>
                      }
                    </div>
                  </mat-list-item>
                  <mat-divider></mat-divider>
                }
              </mat-list>
            }

            @if (auth.isAdmin() && selectedSaleForCancellation(); as sale) {
              <mat-divider></mat-divider>

              <div class="sales-cancellation-card">
                <span class="page-kicker">Anular venta</span>
                <strong>
                  {{ sale.public_id || '#' + sale.id }} · {{ sale.customer?.name || 'Consumidor final' }}
                </strong>
                <mat-form-field appearance="outline">
                  <mat-label>Motivo de anulacion</mat-label>
                  <textarea
                    matInput
                    rows="3"
                    [value]="saleCancellationReason()"
                    (input)="onSaleCancellationReasonChange($event)"
                  ></textarea>
                </mat-form-field>
                <p class="muted sales-warning-text">
                  La anulacion revertira stock y, si el pago fue en efectivo, tambien registrara la
                  salida correspondiente en caja.
                </p>
                <div class="cta-row">
                  <button
                    mat-stroked-button
                    type="button"
                    (click)="clearSaleCancellation()"
                    [disabled]="cancellingSaleId() === sale.id"
                  >
                    Cancelar
                  </button>
                  <button
                    mat-flat-button
                    color="primary"
                    type="button"
                    (click)="cancelSelectedSale()"
                    [disabled]="cancellingSaleId() === sale.id"
                  >
                    Confirmar anulacion
                  </button>
                </div>
              </div>
            }

            @if (loadingSaleDetail()) {
              <mat-divider></mat-divider>
              <mat-progress-bar mode="indeterminate"></mat-progress-bar>
            }

            @if (selectedSaleDetail(); as sale) {
              <mat-divider></mat-divider>

              <div class="sales-detail-card">
                <div class="sales-detail-card__header">
                  <div class="stack">
                    <span class="page-kicker">Detalle de venta</span>
                    <strong>
                      {{ sale.public_id || '#' + sale.id }} ·
                      {{ sale.customer?.name || 'Consumidor final' }}
                    </strong>
                    <span class="muted">
                      {{ describeSaleDocument(sale) }} · {{ formatSaleTimeline(sale) }}
                    </span>
                  </div>

                  <div class="cta-row">
                    <button
                      mat-stroked-button
                      type="button"
                      (click)="refreshSelectedSale()"
                      [disabled]="loadingSaleDetail() || processingReturn()"
                    >
                      Actualizar
                    </button>
                    <button mat-stroked-button type="button" (click)="clearSelectedSale()">
                      Cerrar
                    </button>
                  </div>
                </div>

                <mat-chip-set>
                  <mat-chip>Estado {{ sale.status }}</mat-chip>
                  <mat-chip>Total {{ formatCurrency(sale.grand_total) }}</mat-chip>
                  <mat-chip>Pagado {{ formatCurrency(sale.paid_total) }}</mat-chip>
                  <mat-chip>Neto {{ formatCurrency(sale.net_receivable_total) }}</mat-chip>
                  <mat-chip highlighted>Saldo {{ formatCurrency(sale.balance_due) }}</mat-chip>
                  <mat-chip>Cobro {{ labelForPaymentStatus(sale.payment_status) }}</mat-chip>
                  <mat-chip>Devuelto {{ formatCurrency(sale.returned_total) }}</mat-chip>
                  <mat-chip>Items {{ sale.items.length }}</mat-chip>
                  @if (sale.cash_session?.register_name) {
                    <mat-chip>Caja {{ sale.cash_session?.register_name }}</mat-chip>
                  }
                </mat-chip-set>

                @if (sale.invoice) {
                  <div class="sales-document-banner">
                    <div class="stack">
                      <span class="page-kicker">Factura emitida</span>
                      <strong>{{ sale.invoice.invoice_number }}</strong>
                      <span class="muted">
                        {{ sale.invoice.customer_name }} · {{ formatDateTime(sale.invoice.issued_at) }}
                      </span>
                    </div>

                    <div class="cta-row">
                      <button
                        mat-stroked-button
                        type="button"
                        (click)="downloadInvoicePdf(sale.invoice)"
                        [disabled]="downloadingInvoiceId() === sale.invoice.id"
                      >
                        Descargar PDF
                      </button>
                    </div>
                  </div>

                  <p class="muted sales-warning-text">
                    Esta venta tiene factura. La devolucion operativa ya queda registrada y la nota
                    de credito ya puede emitirse desde cada devolucion registrada.
                  </p>
                }

                <div class="sales-detail-grid">
                  <section class="sales-detail-panel">
                    <span class="page-kicker">Cobros y saldo</span>

                    <div class="sales-return-preview">
                      <mat-chip-set>
                        <mat-chip>Estado {{ labelForPaymentStatus(sale.payment_status) }}</mat-chip>
                        <mat-chip>Pagado {{ formatCurrency(sale.paid_total) }}</mat-chip>
                        <mat-chip highlighted>Saldo {{ formatCurrency(sale.balance_due) }}</mat-chip>
                        <mat-chip>Cobros {{ sale.payments.length }}</mat-chip>
                      </mat-chip-set>

                      @if (sale.status !== 'completed') {
                        <p class="muted">
                          Las ventas anuladas ya no permiten registrar abonos.
                        </p>
                      } @else if (sale.balance_due <= 0) {
                        <p class="muted">
                          Esta venta ya no tiene saldo pendiente por cobrar.
                        </p>
                      } @else {
                        <form
                          class="sales-form"
                          [formGroup]="salePaymentForm"
                          (ngSubmit)="submitSalePayment()"
                        >
                          <mat-form-field appearance="outline">
                            <mat-label>Metodo de cobro</mat-label>
                            <mat-select formControlName="method">
                              @for (option of salePaymentMethods; track option.value) {
                                <mat-option [value]="option.value">{{ option.label }}</mat-option>
                              }
                            </mat-select>
                          </mat-form-field>

                          <mat-form-field appearance="outline">
                            <mat-label>Monto</mat-label>
                            <input matInput type="number" min="0.01" step="0.01" formControlName="amount" />
                          </mat-form-field>

                          <mat-form-field appearance="outline">
                            <mat-label>Referencia</mat-label>
                            <input matInput type="text" formControlName="reference" />
                          </mat-form-field>

                          <mat-form-field appearance="outline">
                            <mat-label>Notas</mat-label>
                            <textarea matInput rows="3" formControlName="notes"></textarea>
                          </mat-form-field>

                          @if (salePaymentForm.getRawValue().method === 'cash' && !draft()?.cash_session) {
                            <p class="muted sales-warning-text">
                              Para registrar cobros en efectivo necesitas una caja abierta en tu
                              usuario actual.
                            </p>
                          }

                          <div class="cta-row">
                            <button
                              mat-flat-button
                              color="primary"
                              type="submit"
                              [disabled]="isSalePaymentSubmitDisabled()"
                            >
                              Registrar abono
                            </button>
                          </div>
                        </form>
                      }
                    </div>
                  </section>

                  <section class="sales-detail-panel">
                    <span class="page-kicker">Historial de cobros</span>

                    @if (sale.payments.length === 0) {
                      <p class="muted">
                        Esta venta todavia no tiene cobros registrados en la API nueva.
                      </p>
                    } @else {
                      <div class="sales-return-history">
                        @for (payment of sale.payments; track payment.id) {
                          <article class="sales-return-history-item">
                            <div class="sales-return-history-item__copy">
                              <strong>
                                {{ payment.public_id || '#' + payment.id }} ·
                                {{ labelForPaymentMethod(payment.method) }}
                              </strong>
                              <span>
                                {{ formatDateTime(payment.paid_at) }}
                                @if (payment.paid_by?.name) {
                                  · {{ payment.paid_by?.name }}
                                }
                              </span>
                              @if (payment.reference || payment.notes) {
                                <small>
                                  {{ payment.reference || 'Sin referencia' }}
                                  @if (payment.notes) {
                                    · {{ payment.notes }}
                                  }
                                </small>
                              }
                              <mat-chip-set>
                                <mat-chip>Monto {{ formatCurrency(payment.amount) }}</mat-chip>
                                @if (payment.cash_session?.register_name) {
                                  <mat-chip>Caja {{ payment.cash_session?.register_name }}</mat-chip>
                                }
                              </mat-chip-set>
                            </div>
                            <div class="sales-return-history-item__actions">
                              <div class="sales-return-history-item__amount">
                                {{ formatCurrency(payment.amount) }}
                              </div>
                            </div>
                          </article>
                        }
                      </div>
                    }
                  </section>
                </div>

                <div class="sales-detail-grid">
                  <section class="sales-detail-panel">
                    <span class="page-kicker">Items vendidos</span>

                    @if (sale.items.length === 0) {
                      <p class="muted">Esta venta no expone items para devolver.</p>
                    } @else {
                      <div class="sales-return-items">
                        @for (item of sale.items; track item.id) {
                          <article class="sales-return-item">
                            <div class="sales-return-item__copy">
                              <strong>{{ item.name_snapshot }}</strong>
                              <small>
                                {{ formatCurrency(item.unit_price) }} por unidad
                                @if (item.product) {
                                  · Stock actual {{ formatNumber(item.product.current_stock) }}
                                }
                              </small>
                              <mat-chip-set>
                                <mat-chip>Vendidas {{ formatNumber(item.quantity) }}</mat-chip>
                                <mat-chip>Devueltas {{ formatNumber(item.returned_quantity) }}</mat-chip>
                                <mat-chip highlighted>
                                  Restantes {{ formatNumber(item.remaining_quantity) }}
                                </mat-chip>
                              </mat-chip-set>
                            </div>

                            @if (sale.status === 'completed' && item.remaining_quantity > 0) {
                              <div class="sales-return-item__entry">
                                <mat-form-field appearance="outline">
                                  <mat-label>Cantidad a devolver</mat-label>
                                  <input
                                    matInput
                                    type="number"
                                    min="0"
                                    [max]="item.remaining_quantity"
                                    step="0.01"
                                    [value]="getReturnQuantity(item.id)"
                                    [disabled]="processingReturn()"
                                    (input)="onReturnQuantityChange(item, $event)"
                                  />
                                </mat-form-field>

                                <button
                                  mat-stroked-button
                                  type="button"
                                  (click)="setMaxReturnQuantity(item)"
                                  [disabled]="processingReturn()"
                                >
                                  Maximo
                                </button>
                              </div>
                            }
                          </article>
                        }
                      </div>
                    }
                  </section>

                  <section class="sales-detail-panel">
                    <span class="page-kicker">Registrar devolucion</span>

                    @if (sale.status !== 'completed') {
                      <p class="muted">
                        Solo las ventas completadas permiten devoluciones parciales.
                      </p>
                    } @else if (!hasReturnableItems(sale)) {
                      <p class="muted">
                        Todos los items de esta venta ya fueron devueltos por completo.
                      </p>
                    } @else {
                      <div class="sales-form">
                        <mat-form-field appearance="outline">
                          <mat-label>Metodo de reembolso</mat-label>
                          <mat-select
                            [value]="saleReturnMethod()"
                            (selectionChange)="onSaleReturnMethodChange($event.value)"
                          >
                            @for (option of refundMethods; track option.value) {
                              <mat-option [value]="option.value">{{ option.label }}</mat-option>
                            }
                          </mat-select>
                        </mat-form-field>

                        <mat-form-field appearance="outline">
                          <mat-label>Motivo general</mat-label>
                          <textarea
                            matInput
                            rows="3"
                            [value]="saleReturnReason()"
                            (input)="onSaleReturnReasonChange($event)"
                          ></textarea>
                        </mat-form-field>

                        <mat-form-field appearance="outline">
                          <mat-label>Notas internas</mat-label>
                          <textarea
                            matInput
                            rows="3"
                            [value]="saleReturnNotes()"
                            (input)="onSaleReturnNotesChange($event)"
                          ></textarea>
                        </mat-form-field>
                      </div>

                      <div class="sales-return-preview">
                        <mat-chip-set>
                          <mat-chip>Items seleccionados {{ selectedReturnEntries().length }}</mat-chip>
                          <mat-chip highlighted>
                            Reembolso {{ formatCurrency(selectedReturnTotal()) }}
                          </mat-chip>
                          <mat-chip>
                            Metodo {{ labelForRefundMethod(saleReturnMethod()) }}
                          </mat-chip>
                        </mat-chip-set>

                        @if (saleReturnMethod() === 'cash' && !draft()?.cash_session) {
                          <p class="muted sales-warning-text">
                            Para devolver efectivo necesitas una caja abierta en tu usuario actual.
                          </p>
                        }

                        @if (selectedReturnEntries().length === 0) {
                          <p class="muted">
                            Marca al menos una cantidad pendiente para registrar la devolucion.
                          </p>
                        } @else {
                          <div class="sales-return-preview-list">
                            @for (entry of selectedReturnEntries(); track entry.item.id) {
                              <article class="sales-return-preview-item">
                                <strong>{{ entry.item.name_snapshot }}</strong>
                                <span>
                                  {{ formatNumber(entry.quantity) }} unidad(es) ·
                                  {{ formatCurrency(entry.refund_total) }}
                                </span>
                              </article>
                            }
                          </div>
                        }

                        <div class="cta-row">
                          <button
                            mat-flat-button
                            color="primary"
                            type="button"
                            (click)="submitReturn()"
                            [disabled]="isReturnSubmitDisabled()"
                          >
                            Registrar devolucion
                          </button>
                        </div>
                      </div>
                    }
                  </section>
                </div>

                <section class="sales-detail-panel">
                  <span class="page-kicker">Historial de devoluciones</span>

                  @if (sale.returns.length === 0) {
                    <p class="muted">
                      Esta venta todavia no tiene devoluciones parciales registradas.
                    </p>
                  } @else {
                    <div class="sales-return-history">
                      @for (saleReturn of sale.returns; track saleReturn.id) {
                        <article class="sales-return-history-item">
                          <div class="sales-return-history-item__copy">
                            <strong>
                              {{ saleReturn.public_id || '#' + saleReturn.id }} ·
                              {{ labelForRefundMethod(saleReturn.refund_method) }}
                            </strong>
                            <span>
                              {{ formatDateTime(saleReturn.returned_at) }}
                              @if (saleReturn.returned_by?.name) {
                                · {{ saleReturn.returned_by?.name }}
                              }
                            </span>
                            <small>{{ saleReturn.reason }}</small>
                            <mat-chip-set>
                              <mat-chip>Total {{ formatCurrency(saleReturn.refund_total) }}</mat-chip>
                              @if (saleReturn.cash_session?.register_name) {
                                <mat-chip>Caja {{ saleReturn.cash_session?.register_name }}</mat-chip>
                              }
                              @if (saleReturn.credit_note) {
                                <mat-chip highlighted>
                                  NC {{ saleReturn.credit_note.credit_note_number }}
                                </mat-chip>
                              }
                              @for (item of saleReturn.items; track item.id) {
                                <mat-chip>
                                  {{ item.name_snapshot }} · {{ formatNumber(item.quantity) }}
                                </mat-chip>
                              }
                            </mat-chip-set>
                          </div>
                          <div class="sales-return-history-item__actions">
                            <div class="sales-return-history-item__amount">
                              {{ formatCurrency(saleReturn.refund_total) }}
                            </div>

                            <div class="sales-history-item__toolbar">
                              @if (saleReturn.credit_note) {
                                <button
                                  mat-stroked-button
                                  type="button"
                                  (click)="downloadCreditNotePdf(saleReturn.credit_note)"
                                  [disabled]="downloadingCreditNoteId() === saleReturn.credit_note.id"
                                >
                                  Descargar NC
                                </button>
                              } @else if (sale.invoice && auth.isAdmin()) {
                                <button
                                  mat-stroked-button
                                  type="button"
                                  (click)="issueCreditNote(saleReturn)"
                                  [disabled]="issuingCreditNoteReturnId() === saleReturn.id"
                                >
                                  Emitir NC
                                </button>
                              }
                            </div>
                          </div>
                        </article>
                      }
                    </div>
                  }
                </section>
              </div>
            }
          </mat-card-content>
        </mat-card>
        }
      </section>
    </section>
  `,
  styles: `
    .sales-page {
      align-content: start;
    }

    .sales-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr) minmax(18rem, 0.8fr);
    }

    .sales-sections {
      display: flex;
      gap: 0.65rem;
      flex-wrap: wrap;
    }

    .sales-section {
      border: 1px solid rgba(148, 163, 184, 0.22);
      border-radius: 999px;
      background: rgba(246, 249, 252, 0.95);
      color: var(--text-muted);
      font-weight: 700;
      min-height: 2.8rem;
      padding: 0 1rem;
    }

    .sales-section.is-active {
      border-color: rgba(22, 138, 87, 0.26);
      background: rgba(22, 138, 87, 0.1);
      color: var(--primary-strong);
    }

    .sales-card {
      height: 100%;
      border-radius: 1.5rem;
    }

    .sales-card mat-card-content:first-of-type {
      margin-top: 1rem;
    }

    .sales-form {
      display: grid;
      gap: 0.9rem;
    }

    .sales-form--checkout {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sales-invoice-meta {
      display: grid;
      gap: 0.5rem;
      grid-column: 1 / -1;
    }

    .sales-payment-meta {
      display: grid;
      gap: 0.5rem;
      grid-column: 1 / -1;
    }

    .draft-items,
    .sales-return-items,
    .sales-return-history,
    .sales-return-preview-list {
      display: grid;
      gap: 0.75rem;
    }

    .draft-item,
    .sales-cancellation-card,
    .sales-detail-card,
    .sales-return-item,
    .sales-return-history-item,
    .sales-return-preview-item {
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: rgba(15, 76, 129, 0.03);
    }

    .draft-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem;
    }

    .draft-item__copy,
    .sales-return-item__copy,
    .sales-return-history-item__copy {
      display: grid;
      gap: 0.45rem;
      min-width: 0;
    }

    .draft-item__copy small,
    .sales-return-item__copy small,
    .sales-history-item__content span,
    .sales-history-item__content small,
    .sales-warning-text,
    .sales-return-history-item__copy span,
    .sales-return-history-item__copy small,
    .sales-return-preview-item span {
      color: var(--text-muted);
    }

    .draft-item__actions,
    .sales-history-item__toolbar,
    .sales-return-item__entry {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      flex-shrink: 0;
    }

    .sales-history-item {
      height: auto;
      align-items: start;
      padding-block: 0.45rem;
    }

    .sales-history-item__content {
      display: grid;
      gap: 0.2rem;
      min-width: 0;
      padding-right: 1rem;
    }

    .sales-history-item__amount,
    .sales-return-history-item__amount {
      font-weight: 700;
      white-space: nowrap;
    }

    .sales-history-item__actions {
      display: grid;
      gap: 0.4rem;
      justify-items: end;
      flex-shrink: 0;
    }

    .sales-history-item__badge {
      border-radius: 999px;
      background: rgba(148, 28, 28, 0.14);
      color: #991b1b;
      font-size: 0.78rem;
      font-weight: 700;
      padding: 0.2rem 0.6rem;
    }

    .sales-success {
      border-color: rgba(19, 128, 77, 0.18);
      background: rgba(19, 128, 77, 0.08);
    }

    .sales-warning {
      border-color: rgba(180, 83, 9, 0.18);
      background: rgba(180, 83, 9, 0.08);
    }

    .sales-chip-warning {
      background: rgba(180, 83, 9, 0.16);
      color: #92400e;
    }

    .sales-cancellation-card,
    .sales-detail-card {
      display: grid;
      gap: 0.85rem;
      margin-top: 1rem;
      padding: 1rem;
    }

    .sales-document-banner {
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 1rem;
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: rgba(15, 76, 129, 0.03);
      padding: 1rem;
    }

    .sales-detail-card__header {
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 1rem;
    }

    .sales-detail-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
    }

    .sales-detail-panel {
      display: grid;
      gap: 0.75rem;
    }

    .sales-return-item {
      display: grid;
      gap: 0.85rem;
      padding: 1rem;
    }

    .sales-return-item__entry {
      align-items: start;
      justify-content: space-between;
    }

    .sales-return-item__entry mat-form-field {
      flex: 1;
    }

    .sales-return-preview {
      display: grid;
      gap: 0.75rem;
    }

    .sales-return-preview-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 0.85rem 1rem;
    }

    .sales-return-history-item {
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem;
    }

    .sales-return-history-item__actions {
      display: grid;
      gap: 0.5rem;
      justify-items: end;
      flex-shrink: 0;
    }

    @media (max-width: 1200px) {
      .sales-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 980px) {
      .sales-detail-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 860px) {
      .sales-layout,
      .sales-form--checkout {
        grid-template-columns: 1fr;
      }

      .draft-item,
      .sales-document-banner,
      .sales-detail-card__header,
      .sales-return-preview-item,
      .sales-return-history-item {
        align-items: start;
        flex-direction: column;
      }

      .draft-item__actions,
      .sales-history-item__toolbar,
      .sales-return-item__entry {
        width: 100%;
        justify-content: flex-end;
      }

      .sales-history-item__actions,
      .sales-return-history-item__actions {
        width: 100%;
      }
    }
  `,
})
export class SalesPageComponent {
  protected readonly auth = inject(AuthService);

  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly salesApi = inject(SalesApiService);
  private readonly productsApi = inject(ProductsApiService);
  private readonly customersApi = inject(CustomersApiService);
  private readonly settingsApi = inject(SettingsApiService);

  protected readonly loading = signal(false);
  protected readonly addingItem = signal(false);
  protected readonly checkingOut = signal(false);
  protected readonly processingPayment = signal(false);
  protected readonly cancellingSaleId = signal<number | null>(null);
  protected readonly downloadingInvoiceId = signal<number | null>(null);
  protected readonly downloadingCreditNoteId = signal<number | null>(null);
  protected readonly issuingCreditNoteReturnId = signal<number | null>(null);
  protected readonly loadingSaleDetail = signal(false);
  protected readonly processingReturn = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);

  protected readonly draft = signal<SaleDraft | null>(null);
  protected readonly products = signal<Product[]>([]);
  protected readonly customers = signal<BusinessPartner[]>([]);
  protected readonly recentSales = signal<SaleRecord[]>([]);
  protected readonly businessSettings = signal<BusinessSettings | null>(null);
  protected readonly selectedProductId = signal<number | null>(null);
  protected readonly selectedDocumentType = signal<string>('ticket');
  protected readonly salesView = signal<'checkout' | 'history'>('checkout');
  protected readonly selectedSaleId = signal<number | null>(null);
  protected readonly selectedSaleDetail = signal<SaleDetail | null>(null);
  protected readonly selectedSaleForCancellationId = signal<number | null>(null);
  protected readonly saleCancellationReason = signal('');
  protected readonly saleReturnMethod = signal('cash');
  protected readonly saleReturnReason = signal(
    'Devolucion parcial registrada desde la nueva plataforma.',
  );
  protected readonly saleReturnNotes = signal('');
  protected readonly saleReturnQuantities = signal<Record<number, number>>({});

  protected readonly selectedProduct = computed(() => {
    const productId = this.selectedProductId();

    if (productId === null) {
      return null;
    }

    return this.products().find((product) => product.id === productId) ?? null;
  });

  protected readonly activeTaxResolution = computed(
    () => this.businessSettings()?.active_tax_resolution ?? null,
  );

  protected readonly selectedSaleForCancellation = computed(() =>
    this.recentSales().find((sale) => sale.id === this.selectedSaleForCancellationId()) ?? null,
  );

  protected readonly selectedReturnEntries = computed(() => {
    const sale = this.selectedSaleDetail();

    if (!sale) {
      return [];
    }

    const quantities = this.saleReturnQuantities();

    return sale.items
      .map((item) => {
        const quantity = this.normalizeReturnQuantity(quantities[item.id] ?? 0, item.remaining_quantity);

        return {
          item,
          quantity,
          refund_total: this.calculateRefundTotal(item, quantity),
        };
      })
      .filter((entry) => entry.quantity > 0);
  });

  protected readonly selectedReturnTotal = computed(() =>
    this.roundCurrency(
      this.selectedReturnEntries().reduce((total, entry) => total + entry.refund_total, 0),
    ),
  );

  protected readonly invoiceBlockReason = computed(() => {
    if (this.selectedDocumentType() !== 'factura') {
      return null;
    }

    if (!this.activeTaxResolution()) {
      return 'No hay dosificacion activa para emitir factura.';
    }

    const customer = this.draft()?.customer;

    if (!customer) {
      return 'Selecciona un cliente antes de emitir factura.';
    }

    if (!customer.document_number) {
      return 'El cliente seleccionado debe tener documento para emitir factura.';
    }

    return null;
  });

  protected readonly paymentMethods = [
    { value: 'cash', label: 'Efectivo' },
    { value: 'card', label: 'Tarjeta' },
    { value: 'transfer', label: 'Transferencia' },
    { value: 'check', label: 'Cheque' },
    { value: 'credit', label: 'Cuenta por cobrar' },
  ];

  protected readonly salePaymentMethods = [
    { value: 'cash', label: 'Efectivo' },
    { value: 'card', label: 'Tarjeta' },
    { value: 'transfer', label: 'Transferencia' },
    { value: 'check', label: 'Cheque' },
  ];

  protected readonly refundMethods = [
    { value: 'cash', label: 'Efectivo' },
    { value: 'card', label: 'Tarjeta' },
    { value: 'transfer', label: 'Transferencia' },
    { value: 'credit', label: 'Saldo a favor' },
  ];

  protected readonly documentTypes = [
    { value: 'ticket', label: 'Ticket' },
    { value: 'factura', label: 'Factura' },
    { value: 'nota', label: 'Nota de venta' },
  ];

  protected readonly draftForm = this.fb.group({
    customer_id: [''],
    notes: [''],
  });

  protected readonly itemForm = this.fb.group({
    product_id: ['', [Validators.required]],
    quantity: [1, [Validators.required, Validators.min(0.01)]],
  });

  protected readonly checkoutForm = this.fb.group({
    document_type: ['ticket', [Validators.required]],
    payment_method: ['cash', [Validators.required]],
    amount_paid: [0, [Validators.required, Validators.min(0)]],
    reference: [''],
  });

  protected readonly salePaymentForm = this.fb.group({
    method: ['cash', [Validators.required]],
    amount: [0, [Validators.required, Validators.min(0.01)]],
    reference: [''],
    notes: [''],
  });

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
      await Promise.all([
        this.loadDraft(),
        this.loadProducts(),
        this.loadCustomers(),
        this.loadRecentSales(),
        this.loadBusinessSettings(),
      ]);
      await this.applyLegacyPrefillFromQuery();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected async loadDraft(): Promise<void> {
    const draft = await this.salesApi.getDraft();

    this.draft.set(draft);
    this.draftForm.patchValue({
      customer_id: draft.customer?.id ? String(draft.customer.id) : '',
      notes: draft.notes ?? '',
    });

    this.syncCheckoutAmount(draft);
  }

  protected async loadProducts(): Promise<void> {
    const products = await this.productsApi.listProducts();
    this.products.set(products.filter((product) => product.is_active));
  }

  protected async loadCustomers(): Promise<void> {
    const customers = await this.customersApi.list();
    this.customers.set(customers.filter((customer) => customer.is_active));
  }

  protected async loadRecentSales(): Promise<void> {
    this.recentSales.set(await this.salesApi.listSales());
  }

  protected async loadBusinessSettings(): Promise<void> {
    const settings = await this.settingsApi.getBusinessSettings();
    this.businessSettings.set(settings);
    this.checkoutForm.patchValue({
      document_type: settings.system_settings.default_document_type || 'ticket',
    });
    this.selectedDocumentType.set(settings.system_settings.default_document_type || 'ticket');
  }

  protected async saveDraftMeta(): Promise<void> {
    try {
      this.error.set(null);
      this.successMessage.set(null);
      const draft = await this.salesApi.updateDraft(this.buildDraftPayload());
      this.draft.set(draft);
      this.syncCheckoutAmount(draft);
    } catch (error) {
      this.error.set(resolveApiError(error));
    }
  }

  protected async addItem(): Promise<void> {
    if (this.itemForm.invalid) {
      this.itemForm.markAllAsTouched();
      return;
    }

    this.addingItem.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const raw = this.itemForm.getRawValue();
      const draft = await this.salesApi.addDraftItem({
        product_id: Number(raw.product_id),
        quantity: Number(raw.quantity),
      });

      this.draft.set(draft);
      this.syncCheckoutAmount(draft);
      this.itemForm.patchValue({
        quantity: 1,
      });
      this.selectedProductId.set(Number(raw.product_id));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.addingItem.set(false);
    }
  }

  protected async increaseQuantity(item: SaleDraftItem): Promise<void> {
    await this.updateItemQuantity(item, item.quantity + 1);
  }

  protected async decreaseQuantity(item: SaleDraftItem): Promise<void> {
    if (item.quantity <= 1) {
      await this.removeItem(item);
      return;
    }

    await this.updateItemQuantity(item, item.quantity - 1);
  }

  protected async removeItem(item: SaleDraftItem): Promise<void> {
    try {
      this.error.set(null);
      const draft = await this.salesApi.removeDraftItem(item.id);
      this.draft.set(draft);
      this.syncCheckoutAmount(draft);
    } catch (error) {
      this.error.set(resolveApiError(error));
    }
  }

  protected async checkout(): Promise<void> {
    if (this.checkoutForm.invalid) {
      this.checkoutForm.markAllAsTouched();
      return;
    }

    if (this.invoiceBlockReason()) {
      this.error.set(this.invoiceBlockReason());
      return;
    }

    this.checkingOut.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const syncedDraft = await this.salesApi.updateDraft(this.buildDraftPayload());
      this.draft.set(syncedDraft);

      const sale = await this.salesApi.checkout(this.buildCheckoutPayload());
      const receivableNote =
        sale.balance_due > 0
          ? ` Saldo pendiente ${this.formatCurrency(sale.balance_due)}.`
          : '';
      this.successMessage.set(
        sale.invoice
          ? `Venta ${sale.public_id || '#' + sale.id} confirmada. Factura ${sale.invoice.invoice_number} emitida por ${this.formatCurrency(sale.grand_total)}.${receivableNote}`
          : `Venta ${sale.public_id || '#' + sale.id} confirmada por ${this.formatCurrency(sale.grand_total)}.${receivableNote}`,
      );

      await Promise.all([
        this.loadDraft(),
        this.loadRecentSales(),
        this.loadProducts(),
        this.loadBusinessSettings(),
      ]);
      await this.loadSaleDetail(sale.id);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.checkingOut.set(false);
    }
  }

  protected async toggleSaleDetail(sale: SaleRecord): Promise<void> {
    this.salesView.set('history');
    if (this.selectedSaleId() === sale.id) {
      this.clearSelectedSale();
      return;
    }

    await this.loadSaleDetail(sale.id);
  }

  protected async refreshSelectedSale(): Promise<void> {
    const saleId = this.selectedSaleId();

    if (saleId === null) {
      return;
    }

    await this.loadSaleDetail(saleId, true);
  }

  protected clearSelectedSale(): void {
    this.selectedSaleId.set(null);
    this.selectedSaleDetail.set(null);
    this.resetSaleReturnForm();
    this.resetSalePaymentForm();
  }

  protected hasReturnableItems(sale: SaleDetail): boolean {
    return sale.items.some((item) => item.remaining_quantity > 0);
  }

  protected getReturnQuantity(itemId: number): number {
    return this.saleReturnQuantities()[itemId] ?? 0;
  }

  protected onReturnQuantityChange(item: SaleItemRecord, event: Event): void {
    const target = event.target as HTMLInputElement;
    const quantity = this.normalizeReturnQuantity(Number(target.value || 0), item.remaining_quantity);

    this.saleReturnQuantities.update((current) => ({
      ...current,
      [item.id]: quantity,
    }));
  }

  protected setMaxReturnQuantity(item: SaleItemRecord): void {
    this.saleReturnQuantities.update((current) => ({
      ...current,
      [item.id]: this.normalizeReturnQuantity(item.remaining_quantity, item.remaining_quantity),
    }));
  }

  protected onSaleReturnMethodChange(value: string | null): void {
    this.saleReturnMethod.set(value?.trim() || 'cash');
  }

  protected onSaleReturnReasonChange(event: Event): void {
    const target = event.target as HTMLTextAreaElement;
    this.saleReturnReason.set(target.value);
  }

  protected onSaleReturnNotesChange(event: Event): void {
    const target = event.target as HTMLTextAreaElement;
    this.saleReturnNotes.set(target.value);
  }

  protected async submitReturn(): Promise<void> {
    const sale = this.selectedSaleDetail();

    if (!sale) {
      return;
    }

    const payload = this.buildReturnPayload();

    if (!payload) {
      return;
    }

    this.processingReturn.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const saleReturn = await this.salesApi.createReturn(sale.id, payload);
      const fiscalNote = sale.invoice
        ? ' Ya puedes emitir la nota de credito desde el historial de devoluciones.'
        : '';

      this.successMessage.set(
        `Devolucion ${saleReturn.public_id || '#' + saleReturn.id} registrada por ${this.formatCurrency(saleReturn.refund_total)}.${fiscalNote}`,
      );

      await Promise.all([this.loadRecentSales(), this.loadProducts()]);
      await this.loadSaleDetail(sale.id);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.processingReturn.set(false);
    }
  }

  protected async submitSalePayment(): Promise<void> {
    const sale = this.selectedSaleDetail();

    if (!sale) {
      return;
    }

    if (this.salePaymentForm.invalid) {
      this.salePaymentForm.markAllAsTouched();
      return;
    }

    const payload = this.buildSalePaymentPayload(sale);

    if (!payload) {
      return;
    }

    this.processingPayment.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const payment = await this.salesApi.registerPayment(sale.id, payload);

      this.successMessage.set(
        `Cobro ${payment.public_id || '#' + payment.id} registrado por ${this.formatCurrency(payment.amount)}.`,
      );

      await this.loadRecentSales();
      await this.loadSaleDetail(sale.id, true);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.processingPayment.set(false);
    }
  }

  protected async issueCreditNote(saleReturn: SaleReturnRecord): Promise<void> {
    if (saleReturn.credit_note) {
      await this.downloadCreditNotePdf(saleReturn.credit_note);
      return;
    }

    this.issuingCreditNoteReturnId.set(saleReturn.id);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const creditNote = await this.salesApi.issueCreditNote(saleReturn.id);

      this.successMessage.set(
        `Nota de credito ${creditNote.credit_note_number} emitida por ${this.formatCurrency(creditNote.grand_total)}.`,
      );

      const saleId = this.selectedSaleId();

      if (saleId !== null) {
        await this.loadSaleDetail(saleId, true);
      }

      await this.downloadCreditNotePdf(creditNote);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.issuingCreditNoteReturnId.set(null);
    }
  }

  protected isCheckoutDisabled(): boolean {
    return (
      this.checkingOut() ||
      (this.draft()?.items?.length ?? 0) === 0 ||
      !this.draft()?.cash_session ||
      this.invoiceBlockReason() !== null
    );
  }

  protected isSalePaymentSubmitDisabled(): boolean {
    const sale = this.selectedSaleDetail();

    return (
      this.processingPayment() ||
      !sale ||
      sale.status !== 'completed' ||
      sale.balance_due <= 0 ||
      this.salePaymentForm.invalid ||
      (this.salePaymentForm.getRawValue().method === 'cash' && !this.draft()?.cash_session)
    );
  }

  protected isReturnSubmitDisabled(): boolean {
    const sale = this.selectedSaleDetail();

    return (
      this.processingReturn() ||
      !sale ||
      sale.status !== 'completed' ||
      !this.hasReturnableItems(sale) ||
      this.selectedReturnEntries().length === 0 ||
      !this.nullableText(this.saleReturnReason()) ||
      (this.saleReturnMethod() === 'cash' && !this.draft()?.cash_session)
    );
  }

  protected labelForDocument(value: string | null): string {
    return this.documentTypes.find((option) => option.value === value)?.label ?? (value || 'Ticket');
  }

  protected labelForRefundMethod(value: string | null): string {
    return this.refundMethods.find((option) => option.value === value)?.label ?? (value || 'Credito');
  }

  protected labelForPaymentMethod(value: string | null): string {
    return (
      this.salePaymentMethods.find((option) => option.value === value)?.label ??
      this.paymentMethods.find((option) => option.value === value)?.label ??
      (value || 'Sin pago')
    );
  }

  protected labelForPaymentStatus(value: string | null): string {
    switch (value) {
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

  protected describeSaleDocument(sale: SaleRecord): string {
    if (sale.invoice?.invoice_number) {
      return `Factura ${sale.invoice.invoice_number}`;
    }

    return this.labelForDocument(sale.document_type);
  }

  protected formatSaleTimeline(sale: SaleRecord): string {
    if (sale.status === 'cancelled' && sale.cancelled_at) {
      return `Anulada ${this.formatDateTime(sale.cancelled_at)}`;
    }

    return this.formatDateTime(sale.sold_at);
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

  protected formatSequence(value: number): string {
    if (!Number.isFinite(value) || value <= 0) {
      return '00000000';
    }

    return String(Math.trunc(value)).padStart(8, '0');
  }

  protected async downloadInvoicePdf(invoice: InvoiceRecord): Promise<void> {
    this.downloadingInvoiceId.set(invoice.id);
    this.error.set(null);

    try {
      const pdf = await this.salesApi.downloadInvoicePdf(invoice.id);
      this.triggerPdfDownload(pdf, this.buildInvoicePdfFileName(invoice));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.downloadingInvoiceId.set(null);
    }
  }

  protected async downloadCreditNotePdf(creditNote: CreditNoteRecord): Promise<void> {
    this.downloadingCreditNoteId.set(creditNote.id);
    this.error.set(null);

    try {
      const pdf = await this.salesApi.downloadCreditNotePdf(creditNote.id);
      this.triggerPdfDownload(pdf, this.buildCreditNotePdfFileName(creditNote));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.downloadingCreditNoteId.set(null);
    }
  }

  protected prepareSaleCancellation(sale: SaleRecord): void {
    this.salesView.set('history');
    this.selectedSaleForCancellationId.set(sale.id);
    this.saleCancellationReason.set(
      sale.cancellation_reason ?? 'Anulacion registrada desde la nueva plataforma.',
    );
  }

  protected setSalesView(view: 'checkout' | 'history'): void {
    this.salesView.set(view);
  }

  protected clearSaleCancellation(): void {
    this.selectedSaleForCancellationId.set(null);
    this.saleCancellationReason.set('');
  }

  protected onSaleCancellationReasonChange(event: Event): void {
    const target = event.target as HTMLTextAreaElement;
    this.saleCancellationReason.set(target.value);
  }

  protected async cancelSelectedSale(): Promise<void> {
    const sale = this.selectedSaleForCancellation();

    if (!sale) {
      return;
    }

    const cancellationReason = this.nullableText(this.saleCancellationReason());

    if (!cancellationReason) {
      this.error.set('Debes registrar un motivo antes de anular la venta.');
      return;
    }

    this.cancellingSaleId.set(sale.id);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const cancelledSale = await this.salesApi.cancelSale(sale.id, {
        cancellation_reason: cancellationReason,
      });

      this.successMessage.set(
        `La venta ${cancelledSale.public_id || '#' + cancelledSale.id} fue anulada y sus movimientos ya quedaron revertidos.`,
      );
      this.clearSaleCancellation();
      await Promise.all([this.loadRecentSales(), this.loadProducts()]);

      if (this.selectedSaleId() === sale.id) {
        await this.loadSaleDetail(sale.id, true);
      }
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.cancellingSaleId.set(null);
    }
  }

  protected onProductSelectionChange(value: string | number | null): void {
    this.selectedProductId.set(value ? Number(value) : null);
  }

  protected onDocumentTypeChange(value: string | null): void {
    this.selectedDocumentType.set(value || 'ticket');
  }

  protected onCheckoutPaymentMethodChange(value: string | null): void {
    const method = value?.trim() || 'cash';
    const currentAmount = Number(this.checkoutForm.getRawValue().amount_paid ?? 0);
    const draftTotal = this.draft()?.grand_total ?? 0;

    if (method === 'credit') {
      this.checkoutForm.patchValue({
        payment_method: method,
        amount_paid: 0,
      });

      return;
    }

    this.checkoutForm.patchValue({
      payment_method: method,
      amount_paid: currentAmount > 0 ? currentAmount : draftTotal,
    });
  }

  private async updateItemQuantity(item: SaleDraftItem, quantity: number): Promise<void> {
    try {
      this.error.set(null);
      const draft = await this.salesApi.updateDraftItem(item.id, {
        quantity,
      });
      this.draft.set(draft);
      this.syncCheckoutAmount(draft);
    } catch (error) {
      this.error.set(resolveApiError(error));
    }
  }

  private async loadSaleDetail(saleId: number, preserveReturnSelection = false): Promise<void> {
    this.loadingSaleDetail.set(true);
    this.error.set(null);

    try {
      const sale = await this.salesApi.getSale(saleId);
      this.selectedSaleId.set(saleId);
      this.selectedSaleDetail.set(sale);
      this.syncSalePaymentForm(sale);

      if (preserveReturnSelection) {
        this.syncReturnQuantitiesWithSale(sale);
      } else {
        this.resetSaleReturnForm(sale);
      }
    } catch (error) {
      this.error.set(resolveApiError(error));
      this.selectedSaleId.set(null);
      this.selectedSaleDetail.set(null);
      this.resetSaleReturnForm();
      this.resetSalePaymentForm();
    } finally {
      this.loadingSaleDetail.set(false);
    }
  }

  private syncReturnQuantitiesWithSale(sale: SaleDetail): void {
    const current = this.saleReturnQuantities();
    const next: Record<number, number> = {};

    for (const item of sale.items) {
      next[item.id] = this.normalizeReturnQuantity(current[item.id] ?? 0, item.remaining_quantity);
    }

    this.saleReturnQuantities.set(next);
  }

  private resetSaleReturnForm(sale: SaleDetail | null = null): void {
    const defaultMethod = this.resolveDefaultRefundMethod(sale);

    this.saleReturnMethod.set(defaultMethod);
    this.saleReturnReason.set('Devolucion parcial registrada desde la nueva plataforma.');
    this.saleReturnNotes.set('');
    this.saleReturnQuantities.set(
      Object.fromEntries((sale?.items ?? []).map((item) => [item.id, 0])),
    );
  }

  private resetSalePaymentForm(sale: SaleRecord | SaleDetail | null = null): void {
    this.salePaymentForm.reset({
      method: 'cash',
      amount: sale?.balance_due && sale.balance_due > 0 ? sale.balance_due : 0,
      reference: '',
      notes: '',
    });
  }

  private syncSalePaymentForm(sale: SaleRecord | SaleDetail | null): void {
    this.resetSalePaymentForm(sale);
  }

  private resolveDefaultRefundMethod(sale: SaleDetail | null): string {
    const preferredMethod = sale?.payment_methods.find((method) =>
      this.refundMethods.some((option) => option.value === method),
    );

    return preferredMethod ?? 'cash';
  }

  private buildDraftPayload(): UpdateSaleDraftPayload {
    const raw = this.draftForm.getRawValue();

    return {
      customer_id: raw.customer_id ? Number(raw.customer_id) : null,
      notes: this.nullableText(raw.notes),
    };
  }

  private buildCheckoutPayload(): CheckoutSalePayload {
    const raw = this.checkoutForm.getRawValue();

    return {
      payment_method: raw.payment_method?.trim() ?? 'cash',
      amount_paid: Number(raw.amount_paid ?? 0),
      reference: this.nullableText(raw.reference),
      document_type: this.nullableText(raw.document_type),
      notes: this.nullableText(this.draftForm.getRawValue().notes),
    };
  }

  private buildSalePaymentPayload(sale: SaleDetail): CreateSalePaymentPayload | null {
    const raw = this.salePaymentForm.getRawValue();
    const amount = Number(raw.amount ?? 0);

    if (!Number.isFinite(amount) || amount <= 0) {
      this.error.set('Debes registrar un monto valido para el abono.');
      return null;
    }

    if (amount > sale.balance_due) {
      this.error.set(`Solo puedes cobrar hasta ${this.formatCurrency(sale.balance_due)} en esta venta.`);
      return null;
    }

    return {
      method: raw.method?.trim() ?? 'cash',
      amount,
      reference: this.nullableText(raw.reference),
      notes: this.nullableText(raw.notes),
    };
  }

  private buildReturnPayload(): CreateSaleReturnPayload | null {
    const reason = this.nullableText(this.saleReturnReason());

    if (!reason) {
      this.error.set('Debes registrar un motivo general para la devolucion.');
      return null;
    }

    const items = this.selectedReturnEntries().map((entry) => ({
      sale_item_id: entry.item.id,
      quantity: entry.quantity,
      reason: null,
    }));

    if (items.length === 0) {
      this.error.set('Selecciona al menos una cantidad pendiente antes de registrar la devolucion.');
      return null;
    }

    return {
      refund_method: this.saleReturnMethod(),
      reason,
      notes: this.nullableText(this.saleReturnNotes()),
      items,
    };
  }

  private calculateRefundTotal(item: SaleItemRecord, quantity: number): number {
    if (item.quantity <= 0 || quantity <= 0) {
      return 0;
    }

    return this.roundCurrency((item.line_total / item.quantity) * quantity);
  }

  private normalizeReturnQuantity(value: number, max: number): number {
    if (!Number.isFinite(value) || value <= 0) {
      return 0;
    }

    return this.roundCurrency(Math.min(value, max));
  }

  private roundCurrency(value: number): number {
    return Math.round(value * 100) / 100;
  }

  private triggerPdfDownload(blob: Blob, fileName: string): void {
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

  private buildInvoicePdfFileName(invoice: InvoiceRecord): string {
    return `factura-${this.sanitizeFileName(invoice.invoice_number, 'documento')}.pdf`;
  }

  private buildCreditNotePdfFileName(creditNote: CreditNoteRecord): string {
    return `nota-credito-${this.sanitizeFileName(creditNote.credit_note_number, 'documento')}.pdf`;
  }

  private sanitizeFileName(value: string, fallback: string): string {
    const normalized = value.trim().replaceAll(/[^A-Za-z0-9._-]+/g, '-').replaceAll(/^[-_.]+|[-_.]+$/g, '');

    return normalized === '' ? fallback : normalized;
  }

  private nullableText(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }

  private async applyLegacyPrefillFromQuery(): Promise<void> {
    const query = this.route.snapshot.queryParamMap;
    const legacySource = (query.get('legacy_source') ?? '').trim();
    const legacyCartAction = (query.get('legacy_cart_action') ?? '').trim();
    const serviceMode = (query.get('service_mode') ?? '').trim();
    const documentType = this.normalizeLegacyDocumentType(query.get('document_type'));
    const paymentMethod = this.normalizeLegacyPaymentMethod(query.get('payment_method'));
    const amountPaid = this.normalizeLegacyAmount(query.get('amount_paid'));
    const customerDocument = this.normalizeLegacyDocumentNumber(query.get('customer_document'));
    const legacyDraftNote = this.nullableText(query.get('draft_note') ?? query.get('notes'));

    const contextualNotice = this.resolveLegacyContextNotice(legacySource, legacyCartAction, serviceMode);
    if (contextualNotice !== null) {
      this.legacyNotice.set(this.mergeLegacyNotice(this.legacyNotice(), contextualNotice));
    }

    if (documentType !== null) {
      this.checkoutForm.patchValue({
        document_type: documentType,
      });
      this.selectedDocumentType.set(documentType);
    }

    if (paymentMethod !== null) {
      this.onCheckoutPaymentMethodChange(paymentMethod);
    }

    const matchedCustomer =
      customerDocument === null
        ? null
        : this.customers().find(
            (customer) =>
              this.normalizeLegacyDocumentNumber(customer.document_number) === customerDocument,
          ) ?? null;

    const currentDraft = this.draft();
    const nextCustomerId = matchedCustomer?.id ?? currentDraft?.customer?.id ?? null;
    const nextNotes = legacyDraftNote ?? this.nullableText(currentDraft?.notes);
    const shouldSyncDraft =
      (matchedCustomer !== null && matchedCustomer.id !== (currentDraft?.customer?.id ?? null)) ||
      nextNotes !== this.nullableText(currentDraft?.notes);

    if (shouldSyncDraft) {
      const draft = await this.salesApi.updateDraft({
        customer_id: nextCustomerId,
        notes: nextNotes,
      });

      this.draft.set(draft);
      this.draftForm.patchValue({
        customer_id: draft.customer?.id ? String(draft.customer.id) : '',
        notes: draft.notes ?? '',
      });
      this.syncCheckoutAmount(draft);
    }

    if (amountPaid !== null) {
      this.checkoutForm.patchValue({
        amount_paid:
          (this.checkoutForm.getRawValue().payment_method?.trim() ?? 'cash') === 'credit'
            ? 0
            : amountPaid,
      });
    }

    if (customerDocument !== null && matchedCustomer === null) {
      this.legacyNotice.set(
        this.mergeLegacyNotice(
          this.legacyNotice(),
          `No encontramos un cliente con documento ${customerDocument}. Selecciona uno antes de cobrar.`,
        ),
      );
      return;
    }

    if (matchedCustomer !== null) {
      this.legacyNotice.set(
        this.mergeLegacyNotice(
          this.legacyNotice(),
          `Se precargo el cliente ${matchedCustomer.name} para continuar el checkout en el POS nuevo.`,
        ),
      );
    }

    if (legacyDraftNote !== null) {
      this.legacyNotice.set(
        this.mergeLegacyNotice(
          this.legacyNotice(),
          'Tambien se intento rescatar una nota del flujo viejo para que no se pierda el contexto del borrador.',
        ),
      );
    }
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch ((value ?? '').trim()) {
      case 'pre-sale-cart':
        return 'La preventa global legacy fue retirada. Ahora cada usuario trabaja con su propio borrador en el POS nuevo.';
      case 'pre-sale-cart-write':
        return 'La insercion directa al carrito legacy fue retirada. Revisa el borrador actual y vuelve a cargar los items desde el POS nuevo.';
      case 'pre-sale-cart-delete':
        return 'La limpieza del carrito legacy fue retirada. El borrador del POS nuevo reemplaza ese flujo antiguo.';
      case 'pre-sale-cart-edit':
        return 'La edicion del carrito legacy fue retirada. Ajusta el borrador directamente desde el POS nuevo.';
      case 'checkout-entry':
        return 'El popup legacy de cobro y factura fue reemplazado por el checkout integrado del POS nuevo.';
      case 'sales-consolidation':
        return 'La consolidacion manual ya no es necesaria. Las ventas quedan confirmadas al momento del checkout.';
      case 'sales-consolidation-write':
        return 'Los comentarios y consolidaciones del flujo viejo quedaron cerrados. Revisa la venta desde el historial nuevo.';
      case 'shell-home':
        return 'La portada legacy del area comercial fue retirada. El equipo de ventas ahora entra directo al POS nuevo.';
      default:
        return null;
    }
  }

  private resolveLegacyContextNotice(
    legacySource: string,
    legacyCartAction: string,
    serviceMode: string,
  ): string | null {
    const messages: string[] = [];

    switch (legacySource) {
      case 'invoice-menu':
        messages.push('La eleccion entre factura y ticket ahora vive dentro del checkout nuevo.');
        break;
      case 'checkout-factura':
        messages.push('Se abrio el checkout con factura preseleccionada desde el flujo legacy.');
        break;
      case 'checkout-ticket':
        messages.push('Se abrio el checkout con ticket preseleccionado desde el flujo legacy.');
        break;
      case 'register-sale':
        messages.push('El registro rapido de venta del sistema viejo ahora aterriza en el checkout del POS nuevo.');
        break;
      case 'register-pre-sale':
        messages.push('La antigua preventa ya no persiste en una bolsa global. Ahora debes confirmar el borrador desde este POS.');
        break;
      case 'consolidation-comment':
        messages.push('Los comentarios del cierre manual antiguo ya no consolidan ventas; quedan solo como referencia operativa.');
        break;
      default:
        break;
    }

    switch (legacyCartAction) {
      case 'add':
        messages.push('Los pedidos viejos de mesa o llevar ya no escriben directamente en la preventa compartida.');
        break;
      case 'delete-item':
        messages.push('La eliminacion puntual del carrito legacy fue reemplazada por el manejo del borrador actual.');
        break;
      case 'clear':
        messages.push('El vaciado completo de la preventa vieja fue reemplazado por el borrador aislado por usuario.');
        break;
      case 'edit':
      case 'update':
        messages.push('La actualizacion del carrito viejo ahora debe hacerse sobre el borrador moderno.');
        break;
      default:
        break;
    }

    switch (serviceMode) {
      case 'mesa':
        messages.push('El pedido legacy para mesa ya no se conserva automaticamente. Recargalo desde el POS nuevo si todavia aplica.');
        break;
      case 'llevar':
        messages.push('El pedido legacy para llevar ya no se conserva automaticamente. Recargalo desde el POS nuevo si todavia aplica.');
        break;
      default:
        break;
    }

    return messages.length === 0 ? null : messages.join(' ');
  }

  private mergeLegacyNotice(base: string | null, extra: string): string {
    const normalizedBase = base?.trim() ?? '';

    if (normalizedBase === '') {
      return extra;
    }

    return `${normalizedBase} ${extra}`;
  }

  private normalizeLegacyDocumentType(value: string | null): 'ticket' | 'factura' | 'nota' | null {
    switch ((value ?? '').trim().toLowerCase()) {
      case 'factura':
        return 'factura';
      case 'nota':
      case 'nota-venta':
      case 'nota_de_venta':
        return 'nota';
      case 'ticket':
      case 'sin-factura':
      case 'sin_factura':
        return 'ticket';
      default:
        return null;
    }
  }

  private normalizeLegacyPaymentMethod(
    value: string | null,
  ): 'cash' | 'card' | 'transfer' | 'check' | 'credit' | null {
    switch ((value ?? '').trim().toLowerCase()) {
      case 'cash':
      case 'efectivo':
        return 'cash';
      case 'card':
      case 'tarjeta':
        return 'card';
      case 'transfer':
      case 'transferencia':
        return 'transfer';
      case 'check':
      case 'cheque':
        return 'check';
      case 'credit':
      case 'credito':
      case 'cuenta-por-cobrar':
      case 'cuenta_por_cobrar':
        return 'credit';
      default:
        return null;
    }
  }

  private normalizeLegacyAmount(value: string | null): number | null {
    const normalized = (value ?? '').trim();

    if (normalized === '') {
      return null;
    }

    const amount = Number(normalized);

    if (!Number.isFinite(amount) || amount < 0) {
      return null;
    }

    return this.roundCurrency(amount);
  }

  private normalizeLegacyDocumentNumber(value: string | null | undefined): string | null {
    const normalized = (value ?? '').replaceAll(/[\s-]+/g, '').trim().toLowerCase();

    return normalized === '' ? null : normalized;
  }

  private syncCheckoutAmount(draft: SaleDraft): void {
    this.checkoutForm.patchValue({
      amount_paid: this.checkoutForm.getRawValue().payment_method === 'credit' ? 0 : draft.grand_total,
    });
  }

  protected checkoutAppliedAmount(): number {
    const draftTotal = this.draft()?.grand_total ?? 0;
    const method = this.checkoutForm.getRawValue().payment_method?.trim() ?? 'cash';
    const amountPaid = Number(this.checkoutForm.getRawValue().amount_paid ?? 0);

    if (!Number.isFinite(amountPaid) || amountPaid <= 0 || method === 'credit') {
      return 0;
    }

    return this.roundCurrency(Math.min(amountPaid, draftTotal));
  }

  protected checkoutBalanceDue(): number {
    const draftTotal = this.draft()?.grand_total ?? 0;

    return this.roundCurrency(Math.max(0, draftTotal - this.checkoutAppliedAmount()));
  }

  protected checkoutChangeTotal(): number {
    const method = this.checkoutForm.getRawValue().payment_method?.trim() ?? 'cash';
    const amountPaid = Number(this.checkoutForm.getRawValue().amount_paid ?? 0);
    const draftTotal = this.draft()?.grand_total ?? 0;

    if (method !== 'cash' || !Number.isFinite(amountPaid) || amountPaid <= draftTotal) {
      return 0;
    }

    return this.roundCurrency(amountPaid - draftTotal);
  }
}
