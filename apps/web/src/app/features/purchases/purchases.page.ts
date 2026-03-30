import { Component, computed, inject, signal } from '@angular/core';
import { AbstractControl, FormArray, FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatDividerModule } from '@angular/material/divider';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSelectModule } from '@angular/material/select';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { SuppliersApiService } from '../suppliers/suppliers.api';
import { BusinessPartner } from '../partners/partners.types';
import { ProductsApiService } from '../products/products.api';
import { Product } from '../products/products.types';
import { PurchasesApiService } from './purchases.api';
import {
  CreatePurchaseOrderPaymentPayload,
  CreatePurchaseReturnPayload,
  PurchaseOrder,
  PurchaseOrderItem,
  PurchaseOrderPayment,
  PurchasePaymentMethod,
  PurchasePaymentStatus,
  PurchaseOrderPayload,
  PurchaseReturn,
  PurchaseOrderStatus,
} from './purchases.types';

@Component({
  selector: 'app-purchases-page',
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatCardModule,
    MatChipsModule,
    MatDividerModule,
    MatFormFieldModule,
    MatIconModule,
    MatInputModule,
    MatProgressBarModule,
    MatSelectModule,
  ],
  template: `
    <section class="stack purchases-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Compras</span>
          <h1 class="page-title">Abastecimiento, recepcion y entrada de stock.</h1>
          <p class="page-description">
            Este modulo ya conecta proveedores, productos y movimientos de stock para registrar
            compras reales, recepciones, devoluciones, pagos y saldo pendiente sobre la API nueva.
          </p>
        </div>
        <span class="pill">Compras + stock + cuentas por pagar</span>
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
        <article class="surface stack purchases-success">
          <span class="page-kicker">Compras</span>
          <strong>{{ successMessage() }}</strong>
        </article>
      }

      @if (legacyNotice()) {
        <article class="surface surface--muted stack">
          <span class="page-kicker">Migracion</span>
          <strong>{{ legacyNotice() }}</strong>
        </article>
      }

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Ordenes registradas</span>
          <strong class="metric-card__value">{{ orders().length }}</strong>
          <span class="metric-card__hint">Historial de compras en la nueva plataforma.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Pendientes de recepcion</span>
          <strong class="metric-card__value">{{ pendingOrdersCount() }}</strong>
          <span class="metric-card__hint">Ordenes abiertas esperando ingreso de mercaderia.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Recibidas</span>
          <strong class="metric-card__value">{{ receivedOrdersCount() }}</strong>
          <span class="metric-card__hint">Compras que ya generaron entrada de stock.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Anuladas</span>
          <strong class="metric-card__value">{{ cancelledOrdersCount() }}</strong>
          <span class="metric-card__hint">Ordenes cerradas con trazabilidad de anulacion.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Monto total</span>
          <strong class="metric-card__value">{{ formatCurrency(totalPurchased()) }}</strong>
          <span class="metric-card__hint">Suma de ordenes listadas en el filtro actual.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Pagado</span>
          <strong class="metric-card__value">{{ formatCurrency(totalPaid()) }}</strong>
          <span class="metric-card__hint">Abonos registrados a proveedor en el filtro actual.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Por pagar</span>
          <strong class="metric-card__value">{{ formatCurrency(totalOutstanding()) }}</strong>
          <span class="metric-card__hint">Saldo pendiente despues de devoluciones y pagos.</span>
        </article>
      </section>

      <article class="surface">
        <div class="purchases-toolbar">
          <mat-form-field appearance="outline" class="purchases-toolbar__search">
            <mat-label>Buscar orden o proveedor</mat-label>
            <input
              matInput
              type="text"
              [value]="search()"
              (input)="onSearch($event)"
              placeholder="OC, proveedor o documento"
            />
          </mat-form-field>

          <mat-form-field appearance="outline">
            <mat-label>Estado</mat-label>
            <mat-select [value]="statusFilter()" (selectionChange)="onStatusChange($event.value)">
              <mat-option value="">Todos</mat-option>
              <mat-option value="ordered">Pendiente</mat-option>
              <mat-option value="received">Recibida</mat-option>
              <mat-option value="cancelled">Anulada</mat-option>
            </mat-select>
          </mat-form-field>

          <div class="cta-row">
            <button mat-stroked-button type="button" (click)="refresh()">Refrescar</button>
            @if (auth.isAdmin()) {
              <button mat-flat-button color="primary" type="button" (click)="resetForm()">Nueva orden</button>
            }
          </div>
        </div>
      </article>

      <section class="purchases-layout">
        <mat-card appearance="outlined" class="purchases-card">
          <mat-card-header>
            <mat-card-title>Ordenes de compra</mat-card-title>
            <mat-card-subtitle>Registro operativo de abastecimiento y recepcion.</mat-card-subtitle>
          </mat-card-header>

          <mat-card-content>
            @if (orders().length === 0) {
              <p class="muted">Todavia no hay ordenes de compra registradas con este filtro.</p>
            } @else {
              <div class="purchase-order-list">
                @for (order of orders(); track order.id) {
                  <article
                    class="purchase-order-row"
                    [class.is-selected]="selectedOrderId() === order.id"
                    (click)="selectOrder(order)"
                  >
                    <div class="purchase-order-row__copy">
                      <div class="purchase-order-row__header">
                        <strong>{{ order.supplier?.name || 'Proveedor sin registro' }}</strong>
                        <mat-chip-set>
                          <mat-chip
                            [class.purchase-chip-received]="order.status === 'received'"
                            [class.purchase-chip-cancelled]="order.status === 'cancelled'"
                          >
                            {{ labelForStatus(order.status) }}
                          </mat-chip>
                          <mat-chip
                            [class.purchase-payment-chip-partial]="order.payment_status === 'partial'"
                            [class.purchase-payment-chip-paid]="order.payment_status === 'paid'"
                            [class.purchase-payment-chip-credit]="order.payment_status === 'credit'"
                          >
                            {{ labelForPaymentStatus(order.payment_status) }}
                          </mat-chip>
                          <mat-chip>{{ order.items_count }} item(s)</mat-chip>
                        </mat-chip-set>
                      </div>

                      <span>
                        {{ order.reference || order.public_id || '#' + order.id }}
                        · {{ formatDate(order.ordered_at) }}
                      </span>
                      <small>
                        Total {{ formatCurrency(order.grand_total) }}
                        @if (order.received_at) {
                          · Recibida {{ formatDateTime(order.received_at) }}
                        }
                        @if (order.cancelled_at) {
                          · Anulada {{ formatDateTime(order.cancelled_at) }}
                        }
                      </small>
                      @if (order.returns_count > 0) {
                        <small>
                          Devuelto {{ formatCurrency(order.returned_total) }} en
                          {{ order.returns_count }} movimiento(s)
                        </small>
                      }
                      <small>
                        Pagado {{ formatCurrency(order.paid_total) }}
                        · Saldo {{ formatCurrency(order.balance_due) }}
                      </small>
                    </div>

                    <div class="purchase-order-row__actions">
                      <button mat-stroked-button type="button" (click)="selectOnly(order, $event)">
                        Ver
                      </button>
                      @if (auth.isAdmin() && order.status === 'ordered') {
                        <button mat-stroked-button type="button" (click)="editOrder(order, $event)">
                          Editar
                        </button>
                        <button
                          mat-flat-button
                          color="primary"
                          type="button"
                          (click)="receiveOrder(order, $event)"
                          [disabled]="receivingId() === order.id"
                        >
                          Recibir
                        </button>
                      }
                      @if (auth.isAdmin() && order.status !== 'cancelled') {
                        <button
                          mat-stroked-button
                          type="button"
                          (click)="prepareCancellation(order, $event)"
                          [disabled]="cancellingId() === order.id"
                        >
                          Anular
                        </button>
                      }
                    </div>
                  </article>
                }
              </div>
            }
          </mat-card-content>
        </mat-card>

        <div class="purchases-side">
          <mat-card appearance="outlined" class="purchases-card">
            <mat-card-header>
              <mat-card-title>Detalle de la orden</mat-card-title>
              <mat-card-subtitle>
                @if (selectedOrder(); as order) {
                  {{ order.reference || order.public_id || '#' + order.id }}
                } @else {
                  Selecciona una orden para revisar sus items.
                }
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content>
              @if (selectedOrder(); as order) {
                <div class="stack">
                  <mat-chip-set>
                    <mat-chip>{{ labelForStatus(order.status) }}</mat-chip>
                    <mat-chip
                      [class.purchase-payment-chip-partial]="order.payment_status === 'partial'"
                      [class.purchase-payment-chip-paid]="order.payment_status === 'paid'"
                      [class.purchase-payment-chip-credit]="order.payment_status === 'credit'"
                    >
                      {{ labelForPaymentStatus(order.payment_status) }}
                    </mat-chip>
                    <mat-chip>{{ formatDate(order.ordered_at) }}</mat-chip>
                    <mat-chip>Proveedor {{ order.supplier?.name || 'Sin proveedor' }}</mat-chip>
                    @if (order.returns_count > 0) {
                      <mat-chip>Devuelto {{ formatCurrency(order.returned_total) }}</mat-chip>
                    }
                    @if (order.payments_count > 0) {
                      <mat-chip>Pagado {{ formatCurrency(order.paid_total) }}</mat-chip>
                    }
                    <mat-chip>Saldo {{ formatCurrency(order.balance_due) }}</mat-chip>
                    @if (order.cancelled_at) {
                      <mat-chip>Anulada {{ formatDateTime(order.cancelled_at) }}</mat-chip>
                    }
                  </mat-chip-set>

                  @if (order.notes) {
                    <p class="muted">{{ order.notes }}</p>
                  }

                  @if (order.cancellation_reason) {
                    <article class="surface surface--muted stack purchases-warning">
                      <span class="page-kicker">Anulacion</span>
                      <strong>{{ order.cancellation_reason }}</strong>
                      <span class="muted">
                        @if (order.cancelled_by?.display_name || order.cancelled_by?.name) {
                          Registrada por {{ order.cancelled_by?.display_name || order.cancelled_by?.name }}
                        } @else {
                          Sin usuario asociado
                        }
                      </span>
                    </article>
                  }

                  <div class="purchase-detail-items">
                    @for (item of order.items; track item.id) {
                      <article class="purchase-detail-item">
                        <div class="purchase-detail-item__copy">
                          <strong>{{ item.name_snapshot }}</strong>
                          <span>
                            {{ formatNumber(item.quantity_ordered) }} × {{ formatCurrency(item.unit_cost) }}
                          </span>
                          <small>
                            Recibido {{ formatNumber(item.quantity_received) }}
                            · Devuelto {{ formatNumber(item.returned_quantity) }}
                            · Restante {{ formatNumber(item.remaining_returnable_quantity) }}
                            @if (item.product) {
                              · Stock actual {{ formatNumber(item.product.current_stock) }}
                            }
                          </small>
                        </div>
                        <div class="purchase-detail-item__amount">
                          {{ formatCurrency(item.line_total) }}
                        </div>
                      </article>
                    }
                  </div>

                  <mat-divider></mat-divider>

                  <div class="purchase-detail-summary">
                    <strong>Total {{ formatCurrency(order.grand_total) }}</strong>
                    <span>
                      @if (order.cancelled_by?.display_name || order.cancelled_by?.name) {
                        Anulada por {{ order.cancelled_by?.display_name || order.cancelled_by?.name }}
                      } @else if (order.received_by?.display_name || order.received_by?.name) {
                        Recibida por {{ order.received_by?.display_name || order.received_by?.name }}
                      } @else if (order.created_by?.display_name || order.created_by?.name) {
                        Creada por {{ order.created_by?.display_name || order.created_by?.name }}
                      } @else {
                        Sin usuario asociado
                      }
                    </span>
                  </div>

                  <mat-divider></mat-divider>

                  <div class="stack">
                    <span class="page-kicker">Cuentas por pagar</span>

                    <div class="purchase-payment-summary">
                      <article class="surface surface--muted purchase-payment-summary__card">
                        <span class="metric-card__label">Neto a pagar</span>
                        <strong>{{ formatCurrency(order.net_payable_total) }}</strong>
                        <small>Total despues de devoluciones.</small>
                      </article>

                      <article class="surface surface--muted purchase-payment-summary__card">
                        <span class="metric-card__label">Pagado</span>
                        <strong>{{ formatCurrency(order.paid_total) }}</strong>
                        <small>{{ order.payments_count }} pago(s) registrados.</small>
                      </article>

                      <article class="surface surface--muted purchase-payment-summary__card">
                        <span class="metric-card__label">Saldo</span>
                        <strong>{{ formatCurrency(order.balance_due) }}</strong>
                        <small>{{ labelForPaymentStatus(order.payment_status) }}</small>
                      </article>
                    </div>

                    @if (order.payment_status === 'credit') {
                      <article class="surface surface--muted stack purchases-warning">
                        <span class="page-kicker">Credito a favor</span>
                        <strong>
                          Las devoluciones ya superan lo pagado y queda un saldo a favor con el proveedor.
                        </strong>
                      </article>
                    }

                    @if (auth.isAdmin() && order.status === 'received') {
                      <div class="stack">
                        <span class="page-kicker">Registrar pago</span>

                        @if (order.balance_due <= 0) {
                          <p class="muted">
                            Esta compra ya no tiene saldo pendiente para nuevos pagos.
                          </p>
                        } @else {
                          <div class="purchase-payment-form-grid">
                            <mat-form-field appearance="outline">
                              <mat-label>Metodo</mat-label>
                              <mat-select
                                [value]="purchasePaymentMethod()"
                                (selectionChange)="onPurchasePaymentMethodChange($event.value)"
                              >
                                @for (paymentMethod of paymentMethods; track paymentMethod.value) {
                                  <mat-option [value]="paymentMethod.value">
                                    {{ paymentMethod.label }}
                                  </mat-option>
                                }
                              </mat-select>
                            </mat-form-field>

                            <mat-form-field appearance="outline">
                              <mat-label>Monto</mat-label>
                              <input
                                matInput
                                type="number"
                                min="0.01"
                                step="0.01"
                                [value]="purchasePaymentAmount()"
                                [disabled]="payingOrderId() === order.id"
                                (input)="onPurchasePaymentAmountChange($event)"
                              />
                            </mat-form-field>

                            <mat-form-field appearance="outline">
                              <mat-label>Referencia</mat-label>
                              <input
                                matInput
                                type="text"
                                [value]="purchasePaymentReference()"
                                [disabled]="payingOrderId() === order.id"
                                (input)="onPurchasePaymentReferenceChange($event)"
                              />
                            </mat-form-field>
                          </div>

                          <mat-form-field appearance="outline">
                            <mat-label>Notas internas</mat-label>
                            <textarea
                              matInput
                              rows="3"
                              [value]="purchasePaymentNotes()"
                              [disabled]="payingOrderId() === order.id"
                              (input)="onPurchasePaymentNotesChange($event)"
                            ></textarea>
                          </mat-form-field>

                          @if (purchasePaymentMethod() === 'cash') {
                            <p class="muted purchases-warning-text">
                              Los pagos en efectivo requieren una caja abierta para el usuario actual.
                            </p>
                          }

                          <mat-chip-set>
                            <mat-chip highlighted>
                              Saldo actual {{ formatCurrency(order.balance_due) }}
                            </mat-chip>
                            <mat-chip>
                              Nuevo saldo estimado
                              {{ formatCurrency(estimatedBalanceAfterPayment(order)) }}
                            </mat-chip>
                          </mat-chip-set>
                          <div class="cta-row">
                            <button
                              mat-stroked-button
                              type="button"
                              (click)="setOutstandingPaymentAmount(order)"
                              [disabled]="payingOrderId() === order.id"
                            >
                              Usar saldo pendiente
                            </button>
                            <button
                              mat-flat-button
                              color="primary"
                              type="button"
                              (click)="createPurchasePayment()"
                              [disabled]="isPaymentDisabled(order)"
                            >
                              Registrar pago
                            </button>
                          </div>
                        }
                      </div>
                    }

                    <div class="stack">
                      <span class="page-kicker">Historial de pagos</span>

                      @if (order.payments.length === 0) {
                        <p class="muted">
                          Esta orden todavia no tiene pagos registrados.
                        </p>
                      } @else {
                        <div class="purchase-payment-history">
                          @for (payment of order.payments; track payment.id) {
                            <article class="purchase-payment-history-item">
                              <div class="purchase-detail-item__copy">
                                <strong>{{ payment.public_id || '#' + payment.id }}</strong>
                                <span>
                                  {{ formatDateTime(payment.paid_at) }}
                                  @if (payment.paid_by?.name) {
                                    · {{ payment.paid_by?.name }}
                                  }
                                  @if (payment.cash_session?.register_name) {
                                    · {{ payment.cash_session?.register_name }}
                                  }
                                </span>
                                <small>
                                  {{ formatPaymentMethod(payment.method) }}
                                  @if (payment.reference) {
                                    · Ref {{ payment.reference }}
                                  }
                                </small>
                                @if (payment.notes) {
                                  <small>{{ payment.notes }}</small>
                                }
                              </div>
                              <div class="purchase-detail-item__amount">
                                {{ formatCurrency(payment.amount) }}
                              </div>
                            </article>
                          }
                        </div>
                      }
                    </div>
                  </div>

                  @if (auth.isAdmin() && order.status === 'received') {
                    <mat-divider></mat-divider>

                    <div class="stack">
                      <span class="page-kicker">Devolucion a proveedor</span>

                      @if (!hasReturnableItems(order)) {
                        <p class="muted">
                          Todos los items recibidos ya fueron devueltos por completo.
                        </p>
                      } @else {
                        <div class="purchase-return-items">
                          @for (item of order.items; track item.id) {
                            @if (item.remaining_returnable_quantity > 0) {
                              <article class="purchase-return-item">
                                <div class="purchase-detail-item__copy">
                                  <strong>{{ item.name_snapshot }}</strong>
                                  <span>
                                    Devuelto {{ formatNumber(item.returned_quantity) }} de
                                    {{ formatNumber(item.quantity_received) }}
                                  </span>
                                  <small>
                                    Restante {{ formatNumber(item.remaining_returnable_quantity) }} ·
                                    Costo {{ formatCurrency(item.unit_cost) }}
                                  </small>
                                </div>

                                <div class="purchase-return-item__entry">
                                  <mat-form-field appearance="outline">
                                    <mat-label>Cantidad a devolver</mat-label>
                                    <input
                                      matInput
                                      type="number"
                                      min="0"
                                      [max]="item.remaining_returnable_quantity"
                                      step="0.01"
                                      [value]="getReturnQuantity(item.id)"
                                      [disabled]="returningOrderId() === order.id"
                                      (input)="onReturnQuantityChange(item, $event)"
                                    />
                                  </mat-form-field>

                                  <button
                                    mat-stroked-button
                                    type="button"
                                    (click)="setMaxReturnQuantity(item)"
                                    [disabled]="returningOrderId() === order.id"
                                  >
                                    Maximo
                                  </button>
                                </div>
                              </article>
                            }
                          }
                        </div>

                        <mat-form-field appearance="outline">
                          <mat-label>Motivo general</mat-label>
                          <textarea
                            matInput
                            rows="3"
                            [value]="purchaseReturnReason()"
                            (input)="onPurchaseReturnReasonChange($event)"
                          ></textarea>
                        </mat-form-field>

                        <mat-form-field appearance="outline">
                          <mat-label>Notas internas</mat-label>
                          <textarea
                            matInput
                            rows="3"
                            [value]="purchaseReturnNotes()"
                            (input)="onPurchaseReturnNotesChange($event)"
                          ></textarea>
                        </mat-form-field>

                        <div class="cta-row">
                          <mat-chip-set>
                            <mat-chip>Items seleccionados {{ selectedReturnEntries().length }}</mat-chip>
                            <mat-chip highlighted>
                              Total devuelto {{ formatCurrency(selectedReturnTotal()) }}
                            </mat-chip>
                          </mat-chip-set>
                        </div>

                        <div class="cta-row">
                          <button
                            mat-flat-button
                            color="primary"
                            type="button"
                            (click)="createPurchaseReturn()"
                            [disabled]="isPurchaseReturnDisabled(order)"
                          >
                            Registrar devolucion
                          </button>
                        </div>
                      }
                    </div>
                  }

                  <mat-divider></mat-divider>

                  <div class="stack">
                    <span class="page-kicker">Historial de devoluciones</span>

                    @if (order.returns.length === 0) {
                      <p class="muted">
                        Esta orden todavia no tiene devoluciones parciales registradas.
                      </p>
                    } @else {
                      <div class="purchase-return-history">
                        @for (purchaseReturn of order.returns; track purchaseReturn.id) {
                          <article class="purchase-return-history-item">
                            <div class="purchase-detail-item__copy">
                              <strong>{{ purchaseReturn.public_id || '#' + purchaseReturn.id }}</strong>
                              <span>
                                {{ formatDateTime(purchaseReturn.returned_at) }}
                                @if (purchaseReturn.returned_by?.display_name || purchaseReturn.returned_by?.name) {
                                  · {{ purchaseReturn.returned_by?.display_name || purchaseReturn.returned_by?.name }}
                                }
                              </span>
                              <small>{{ purchaseReturn.reason }}</small>
                              <mat-chip-set>
                                <mat-chip>Total {{ formatCurrency(purchaseReturn.return_total) }}</mat-chip>
                                @for (item of purchaseReturn.items; track item.id) {
                                  <mat-chip>
                                    {{ item.name_snapshot }} · {{ formatNumber(item.quantity) }}
                                  </mat-chip>
                                }
                              </mat-chip-set>
                            </div>
                            <div class="purchase-detail-item__amount">
                              {{ formatCurrency(purchaseReturn.return_total) }}
                            </div>
                          </article>
                        }
                      </div>
                    }
                  </div>

                  @if (auth.isAdmin() && order.status !== 'cancelled') {
                    <mat-divider></mat-divider>

                    <div class="stack">
                      <span class="page-kicker">Anular orden</span>
                      @if (order.payments_count > 0) {
                        <p class="muted purchases-warning-text">
                          La anulacion queda bloqueada porque la compra ya tiene pagos registrados.
                        </p>
                      }
                      <mat-form-field appearance="outline">
                        <mat-label>Motivo de anulacion</mat-label>
                        <textarea
                          matInput
                          rows="3"
                          [value]="purchaseCancellationReason()"
                          (input)="onPurchaseCancellationReasonChange($event)"
                        ></textarea>
                      </mat-form-field>

                      @if (order.status === 'received') {
                        <p class="muted purchases-warning-text">
                          Esta accion revertira la entrada de stock de la compra, siempre que el
                          inventario actual alcance para hacer la reversa.
                        </p>
                      }

                      <div class="cta-row">
                        <button
                          mat-stroked-button
                          type="button"
                          (click)="cancelSelectedOrder()"
                          [disabled]="cancellingId() === order.id || order.payments_count > 0"
                        >
                          Confirmar anulacion
                        </button>
                      </div>
                    </div>
                  }
                </div>
              } @else {
                <p class="muted">Aqui veras el detalle, los items y el estado de la orden seleccionada.</p>
              }
            </mat-card-content>
          </mat-card>

          @if (auth.isAdmin()) {
            <mat-card appearance="outlined" class="purchases-card">
              <mat-card-header>
                <mat-card-title>
                  @if (editingOrderId()) {
                    Editar orden de compra
                  } @else {
                    Nueva orden de compra
                  }
                </mat-card-title>
                <mat-card-subtitle>Preparar abastecimiento y dejar recepcion lista.</mat-card-subtitle>
              </mat-card-header>

              <mat-card-content class="stack" [formGroup]="form">
                <div class="purchases-form-grid">
                  <mat-form-field appearance="outline">
                    <mat-label>Proveedor</mat-label>
                    <mat-select formControlName="supplier_id">
                      @for (supplier of suppliers(); track supplier.id) {
                        <mat-option [value]="supplier.id">{{ supplier.name }}</mat-option>
                      }
                    </mat-select>
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Referencia</mat-label>
                    <input matInput type="text" formControlName="reference" />
                  </mat-form-field>
                </div>

                <div class="purchases-form-grid">
                  <mat-form-field appearance="outline">
                    <mat-label>Fecha de orden</mat-label>
                    <input matInput type="date" formControlName="ordered_at" />
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Notas</mat-label>
                    <textarea matInput rows="3" formControlName="notes"></textarea>
                  </mat-form-field>
                </div>

                <mat-divider></mat-divider>

                <div class="purchases-items-head">
                  <div>
                    <span class="page-kicker">Items</span>
                    <h3 class="page-title purchases-form__title">Detalle de compra</h3>
                  </div>
                  <button mat-stroked-button type="button" (click)="addItemRow()">
                    Agregar item
                  </button>
                </div>

                <div class="purchase-form-items">
                  @for (itemGroup of itemControls(); track $index; let index = $index) {
                    <article class="purchase-form-item" [formGroup]="asFormGroup(itemGroup)">
                      <div class="purchase-form-item__grid">
                        <mat-form-field appearance="outline">
                          <mat-label>Producto</mat-label>
                          <mat-select
                            formControlName="product_id"
                            (selectionChange)="onProductChange(index, $event.value)"
                          >
                            @for (product of products(); track product.id) {
                              <mat-option [value]="product.id">
                                {{ product.name }} · Costo {{ formatCurrency(product.cost_price) }}
                              </mat-option>
                            }
                          </mat-select>
                        </mat-form-field>

                        <mat-form-field appearance="outline">
                          <mat-label>Cantidad</mat-label>
                          <input matInput type="number" min="0.01" step="0.01" formControlName="quantity_ordered" />
                        </mat-form-field>

                        <mat-form-field appearance="outline">
                          <mat-label>Costo unitario</mat-label>
                          <input matInput type="number" min="0" step="0.01" formControlName="unit_cost" />
                        </mat-form-field>
                      </div>

                      <div class="purchase-form-item__meta">
                        @if (productForRow(index); as product) {
                          <mat-chip-set>
                            <mat-chip>Stock {{ formatNumber(product.current_stock) }}</mat-chip>
                            <mat-chip>Unidad {{ product.unit }}</mat-chip>
                            <mat-chip>Venta {{ formatCurrency(product.sale_price) }}</mat-chip>
                          </mat-chip-set>
                        }

                        <button
                          mat-icon-button
                          type="button"
                          (click)="removeItemRow(index)"
                          [disabled]="itemControls().length === 1"
                        >
                          <mat-icon>delete</mat-icon>
                        </button>
                      </div>

                      <mat-form-field appearance="outline">
                        <mat-label>Nota del item</mat-label>
                        <input matInput type="text" formControlName="notes" />
                      </mat-form-field>
                    </article>
                  }
                </div>

                <mat-chip-set>
                  <mat-chip>{{ itemControls().length }} linea(s)</mat-chip>
                  <mat-chip highlighted>Total estimado {{ formatCurrency(formTotal()) }}</mat-chip>
                </mat-chip-set>

                <div class="cta-row">
                  <button mat-stroked-button type="button" (click)="resetForm()">Limpiar</button>
                  <button mat-flat-button color="primary" type="button" (click)="save()" [disabled]="saving()">
                    @if (editingOrderId()) {
                      Actualizar orden
                    } @else {
                      Crear orden
                    }
                  </button>
                </div>
              </mat-card-content>
            </mat-card>
          } @else {
            <mat-card appearance="outlined" class="purchases-card">
              <mat-card-header>
                <mat-card-title>Permisos</mat-card-title>
                <mat-card-subtitle>Consulta habilitada, gestion restringida a admin.</mat-card-subtitle>
              </mat-card-header>
              <mat-card-content>
                <p class="muted">
                  Tu rol puede revisar ordenes y recepciones, pero la creacion o edicion se reserva
                  a administracion.
                </p>
              </mat-card-content>
            </mat-card>
          }
        </div>
      </section>
    </section>
  `,
  styles: `
    .purchases-page {
      align-content: start;
    }

    .purchases-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(0, 1.1fr) minmax(22rem, 0.9fr);
    }

    .purchases-side {
      display: grid;
      gap: 1rem;
      align-content: start;
    }

    .purchases-card {
      border-radius: 1.5rem;
      height: fit-content;
    }

    .purchases-card mat-card-content:first-of-type {
      margin-top: 1rem;
    }

    .purchases-success {
      border-color: rgba(19, 128, 77, 0.18);
      background: rgba(19, 128, 77, 0.08);
    }

    .purchases-warning {
      border-color: rgba(180, 83, 9, 0.18);
      background: rgba(180, 83, 9, 0.08);
    }

    .purchases-toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
    }

    .purchases-toolbar__search {
      min-width: min(24rem, 100%);
      flex: 1 1 20rem;
    }

    .purchase-order-list,
    .purchase-detail-items,
    .purchase-form-items,
    .purchase-payment-history,
    .purchase-payment-summary,
    .purchase-return-items,
    .purchase-return-history {
      display: grid;
      gap: 0.85rem;
    }

    .purchase-order-row,
    .purchase-detail-item,
    .purchase-payment-history-item,
    .purchase-form-item,
    .purchase-return-item,
    .purchase-return-history-item {
      display: grid;
      gap: 0.75rem;
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: rgba(15, 76, 129, 0.03);
      padding: 1rem;
    }

    .purchase-order-row {
      cursor: pointer;
      transition:
        border-color 160ms ease,
        transform 160ms ease,
        background 160ms ease;
    }

    .purchase-order-row:hover,
    .purchase-order-row.is-selected {
      border-color: rgba(15, 76, 129, 0.35);
      background: rgba(15, 76, 129, 0.08);
      transform: translateY(-1px);
    }

    .purchase-order-row__header,
    .purchase-order-row__actions,
    .purchase-form-item__meta,
    .purchase-detail-item,
    .purchase-payment-history-item,
    .purchase-return-item__entry,
    .purchase-return-history-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .purchase-order-row__copy,
    .purchase-detail-item__copy,
    .purchase-detail-summary {
      display: grid;
      gap: 0.2rem;
      min-width: 0;
    }

    .purchase-order-row__copy span,
    .purchase-order-row__copy small,
    .purchase-detail-item__copy span,
    .purchase-detail-item__copy small,
    .purchase-detail-summary span {
      color: var(--text-muted);
    }

    .purchase-detail-item__amount {
      font-weight: 700;
      white-space: nowrap;
    }

    .purchase-return-item__entry mat-form-field {
      flex: 1;
    }

    .purchase-payment-summary {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .purchase-payment-summary__card {
      display: grid;
      gap: 0.25rem;
      border-radius: 1rem;
    }

    .purchase-payment-form-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .purchases-form-grid,
    .purchase-form-item__grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .purchase-form-item__grid {
      grid-template-columns: minmax(0, 1.3fr) minmax(8rem, 0.55fr) minmax(10rem, 0.7fr);
    }

    .purchases-items-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .purchases-form__title {
      font-size: 1.15rem;
    }

    .purchase-chip-received {
      background: rgba(19, 128, 77, 0.14);
      color: #166534;
    }

    .purchase-chip-cancelled {
      background: rgba(148, 28, 28, 0.14);
      color: #991b1b;
    }

    .purchase-payment-chip-partial {
      background: rgba(180, 83, 9, 0.14);
      color: #92400e;
    }

    .purchase-payment-chip-paid {
      background: rgba(19, 128, 77, 0.14);
      color: #166534;
    }

    .purchase-payment-chip-credit {
      background: rgba(15, 76, 129, 0.14);
      color: #0f4c81;
    }

    .purchases-warning-text {
      color: var(--text-muted);
    }

    @media (max-width: 1180px) {
      .purchases-layout,
      .purchase-payment-form-grid,
      .purchase-payment-summary,
      .purchases-form-grid,
      .purchase-form-item__grid {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class PurchasesPageComponent {
  protected readonly auth = inject(AuthService);

  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly purchasesApi = inject(PurchasesApiService);
  private readonly suppliersApi = inject(SuppliersApiService);
  private readonly productsApi = inject(ProductsApiService);

  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly receivingId = signal<number | null>(null);
  protected readonly cancellingId = signal<number | null>(null);
  protected readonly payingOrderId = signal<number | null>(null);
  protected readonly returningOrderId = signal<number | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);

  protected readonly orders = signal<PurchaseOrder[]>([]);
  protected readonly suppliers = signal<BusinessPartner[]>([]);
  protected readonly products = signal<Product[]>([]);
  protected readonly search = signal('');
  protected readonly statusFilter = signal('');
  protected readonly selectedOrderId = signal<number | null>(null);
  protected readonly editingOrderId = signal<number | null>(null);
  protected readonly purchaseCancellationReason = signal('');
  protected readonly purchasePaymentMethod = signal<PurchasePaymentMethod>('transfer');
  protected readonly purchasePaymentAmount = signal(0);
  protected readonly purchasePaymentReference = signal('');
  protected readonly purchasePaymentNotes = signal('');
  protected readonly purchaseReturnReason = signal(
    'Devolucion parcial registrada desde la nueva plataforma.',
  );
  protected readonly purchaseReturnNotes = signal('');
  protected readonly purchaseReturnQuantities = signal<Record<number, number>>({});

  protected readonly selectedOrder = computed(() =>
    this.orders().find((order) => order.id === this.selectedOrderId()) ?? null,
  );

  protected readonly pendingOrdersCount = computed(
    () => this.orders().filter((order) => order.status === 'ordered').length,
  );

  protected readonly receivedOrdersCount = computed(
    () => this.orders().filter((order) => order.status === 'received').length,
  );

  protected readonly cancelledOrdersCount = computed(
    () => this.orders().filter((order) => order.status === 'cancelled').length,
  );

  protected readonly totalPurchased = computed(() =>
    this.orders().reduce((total, order) => total + order.grand_total, 0),
  );

  protected readonly totalPaid = computed(() =>
    this.orders().reduce((total, order) => total + order.paid_total, 0),
  );

  protected readonly totalOutstanding = computed(() =>
    this.orders().reduce((total, order) => total + Math.max(0, order.balance_due), 0),
  );

  protected readonly selectedReturnEntries = computed(() => {
    const order = this.selectedOrder();

    if (!order) {
      return [];
    }

    const quantities = this.purchaseReturnQuantities();

    return order.items
      .map((item) => {
        const quantity = this.normalizeReturnQuantity(
          quantities[item.id] ?? 0,
          item.remaining_returnable_quantity,
        );

        return {
          item,
          quantity,
          line_total: this.roundCurrency(quantity * item.unit_cost),
        };
      })
      .filter((entry) => entry.quantity > 0);
  });

  protected readonly selectedReturnTotal = computed(() =>
    this.roundCurrency(
      this.selectedReturnEntries().reduce((total, entry) => total + entry.line_total, 0),
    ),
  );

  protected readonly form = this.fb.group({
    supplier_id: ['', [Validators.required]],
    reference: [''],
    ordered_at: [this.todayDateInput(), [Validators.required]],
    notes: [''],
    items: this.fb.array([this.createItemGroup()]),
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

  protected readonly paymentMethods: Array<{ value: PurchasePaymentMethod; label: string }> = [
    { value: 'transfer', label: 'Transferencia' },
    { value: 'cash', label: 'Efectivo' },
    { value: 'card', label: 'Tarjeta' },
    { value: 'check', label: 'Cheque' },
  ];

  public constructor() {
    this.legacyNotice.set(this.resolveLegacyNotice(this.route.snapshot.queryParamMap.get('legacy')));
    void this.load();
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    try {
      await Promise.all([this.loadOrders(), this.loadSuppliers(), this.loadProducts()]);
      this.applyLegacyPrefillFromQuery();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected async refresh(): Promise<void> {
    this.successMessage.set(null);
    await this.loadOrders();
  }

  protected async loadOrders(): Promise<void> {
    const orders = await this.purchasesApi.list(this.search(), this.statusFilter());
    this.orders.set(orders);

    if (orders.length === 0) {
      this.selectedOrderId.set(null);
      this.purchaseCancellationReason.set('');
      this.resetPurchasePaymentForm();
      this.resetPurchaseReturnForm();
      return;
    }

    const currentSelectedId = this.selectedOrderId();
    const selected = currentSelectedId ? orders.find((order) => order.id === currentSelectedId) : null;
    const nextSelected = selected ?? orders[0] ?? null;
    this.selectedOrderId.set(nextSelected?.id ?? null);
    this.purchaseCancellationReason.set(nextSelected?.cancellation_reason ?? '');
    this.resetPurchasePaymentForm(nextSelected);
    this.resetPurchaseReturnForm(nextSelected);
  }

  protected async loadSuppliers(): Promise<void> {
    this.suppliers.set((await this.suppliersApi.list()).filter((supplier) => supplier.is_active));
  }

  protected async loadProducts(): Promise<void> {
    this.products.set((await this.productsApi.listProducts()).filter((product) => product.is_active));
  }

  protected onSearch(event: Event): void {
    const target = event.target as HTMLInputElement;
    this.search.set(target.value);
    void this.loadOrders();
  }

  protected onStatusChange(value: string): void {
    this.statusFilter.set(value ?? '');
    void this.loadOrders();
  }

  protected selectOrder(order: PurchaseOrder): void {
    this.selectedOrderId.set(order.id);
    this.purchaseCancellationReason.set(order.cancellation_reason ?? '');
    this.resetPurchasePaymentForm(order);
    this.resetPurchaseReturnForm(order);
  }

  protected selectOnly(order: PurchaseOrder, event: Event): void {
    event.stopPropagation();
    this.selectOrder(order);
  }

  protected editOrder(order: PurchaseOrder, event: Event): void {
    event.stopPropagation();
    this.selectedOrderId.set(order.id);
    this.editingOrderId.set(order.id);
    this.purchaseCancellationReason.set(order.cancellation_reason ?? '');
    this.resetPurchasePaymentForm(order);
    this.resetPurchaseReturnForm(order);
    this.applyOrderToForm(order);
  }

  protected prepareCancellation(order: PurchaseOrder, event: Event): void {
    event.stopPropagation();
    this.selectOrder(order);

    if (this.purchaseCancellationReason().trim() !== '') {
      return;
    }

    this.purchaseCancellationReason.set(
      order.status === 'received'
        ? 'Anulacion de compra recibida por inconsistencia con proveedor.'
        : 'Anulacion de orden de compra.',
    );
  }

  protected async receiveOrder(order: PurchaseOrder, event: Event): Promise<void> {
    event.stopPropagation();

    this.receivingId.set(order.id);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const receivedOrder = await this.purchasesApi.receive(order.id, {
        received_at: new Date().toISOString(),
        notes: order.notes,
      });

      this.successMessage.set(
        `La orden ${receivedOrder.reference || receivedOrder.public_id || '#' + receivedOrder.id} fue recibida y ya ingreso stock.`,
      );
      this.editingOrderId.set(null);
      this.selectedOrderId.set(receivedOrder.id);
      this.resetForm(false);
      await Promise.all([this.loadOrders(), this.loadProducts()]);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.receivingId.set(null);
    }
  }

  protected async save(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.itemsArray.controls.forEach((control) => control.markAllAsTouched());
      return;
    }

    this.saving.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const payload = this.buildPayload();
      const purchaseOrder = this.editingOrderId()
        ? await this.purchasesApi.update(this.editingOrderId()!, payload)
        : await this.purchasesApi.create(payload);

      this.successMessage.set(
        this.editingOrderId()
          ? 'La orden de compra fue actualizada correctamente.'
          : 'La orden de compra fue creada correctamente.',
      );
      this.selectedOrderId.set(purchaseOrder.id);
      this.editingOrderId.set(null);
      this.resetForm(false);
      await this.loadOrders();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected onPurchaseCancellationReasonChange(event: Event): void {
    const target = event.target as HTMLTextAreaElement;
    this.purchaseCancellationReason.set(target.value);
  }

  protected onPurchasePaymentMethodChange(value: PurchasePaymentMethod): void {
    this.purchasePaymentMethod.set(value ?? 'transfer');
  }

  protected onPurchasePaymentAmountChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    this.purchasePaymentAmount.set(this.normalizePaymentAmount(Number(target.value || 0)));
  }

  protected onPurchasePaymentReferenceChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    this.purchasePaymentReference.set(target.value);
  }

  protected onPurchasePaymentNotesChange(event: Event): void {
    const target = event.target as HTMLTextAreaElement;
    this.purchasePaymentNotes.set(target.value);
  }

  protected setOutstandingPaymentAmount(order: PurchaseOrder): void {
    this.purchasePaymentAmount.set(this.normalizePaymentAmount(Math.max(0, order.balance_due)));
  }

  protected isPaymentDisabled(order: PurchaseOrder): boolean {
    const amount = this.normalizePaymentAmount(this.purchasePaymentAmount());

    return (
      this.payingOrderId() === order.id ||
      order.status !== 'received' ||
      order.balance_due <= 0 ||
      amount <= 0 ||
      amount > order.balance_due
    );
  }

  protected async createPurchasePayment(): Promise<void> {
    const order = this.selectedOrder();

    if (!order) {
      return;
    }

    const payload = this.buildPurchasePaymentPayload(order);

    if (!payload) {
      return;
    }

    this.payingOrderId.set(order.id);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const payment = await this.purchasesApi.createPayment(order.id, payload);

      this.successMessage.set(
        `El pago ${payment.public_id || '#' + payment.id} fue registrado por ${this.formatCurrency(payment.amount)} en ${this.formatPaymentMethod(payment.method).toLowerCase()}.`,
      );

      await this.loadOrders();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.payingOrderId.set(null);
    }
  }

  protected async cancelSelectedOrder(): Promise<void> {
    const order = this.selectedOrder();

    if (!order) {
      return;
    }

    const cancellationReason = this.nullableText(this.purchaseCancellationReason());

    if (!cancellationReason) {
      this.error.set('Debes registrar un motivo antes de anular la orden.');
      return;
    }

    this.cancellingId.set(order.id);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const cancelledOrder = await this.purchasesApi.cancel(order.id, {
        cancellation_reason: cancellationReason,
      });

      this.successMessage.set(
        cancelledOrder.status === 'cancelled' && order.status === 'received'
          ? `La orden ${cancelledOrder.reference || cancelledOrder.public_id || '#' + cancelledOrder.id} fue anulada y el stock ya se revirtio.`
          : `La orden ${cancelledOrder.reference || cancelledOrder.public_id || '#' + cancelledOrder.id} fue anulada correctamente.`,
      );
      this.selectedOrderId.set(cancelledOrder.id);
      this.editingOrderId.set(null);
      this.purchaseCancellationReason.set(cancelledOrder.cancellation_reason ?? '');
      this.resetForm(false);
      await Promise.all([this.loadOrders(), this.loadProducts()]);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.cancellingId.set(null);
    }
  }

  protected hasReturnableItems(order: PurchaseOrder): boolean {
    return order.items.some((item) => item.remaining_returnable_quantity > 0);
  }

  protected getReturnQuantity(itemId: number): number {
    return this.purchaseReturnQuantities()[itemId] ?? 0;
  }

  protected onReturnQuantityChange(item: PurchaseOrderItem, event: Event): void {
    const target = event.target as HTMLInputElement;
    const quantity = this.normalizeReturnQuantity(
      Number(target.value || 0),
      item.remaining_returnable_quantity,
    );

    this.purchaseReturnQuantities.update((current) => ({
      ...current,
      [item.id]: quantity,
    }));
  }

  protected setMaxReturnQuantity(item: PurchaseOrderItem): void {
    this.purchaseReturnQuantities.update((current) => ({
      ...current,
      [item.id]: this.normalizeReturnQuantity(
        item.remaining_returnable_quantity,
        item.remaining_returnable_quantity,
      ),
    }));
  }

  protected onPurchaseReturnReasonChange(event: Event): void {
    const target = event.target as HTMLTextAreaElement;
    this.purchaseReturnReason.set(target.value);
  }

  protected onPurchaseReturnNotesChange(event: Event): void {
    const target = event.target as HTMLTextAreaElement;
    this.purchaseReturnNotes.set(target.value);
  }

  protected isPurchaseReturnDisabled(order: PurchaseOrder): boolean {
    return (
      this.returningOrderId() === order.id ||
      order.status !== 'received' ||
      !this.hasReturnableItems(order) ||
      this.selectedReturnEntries().length === 0 ||
      !this.nullableText(this.purchaseReturnReason())
    );
  }

  protected async createPurchaseReturn(): Promise<void> {
    const order = this.selectedOrder();

    if (!order) {
      return;
    }

    const payload = this.buildPurchaseReturnPayload();

    if (!payload) {
      return;
    }

    this.returningOrderId.set(order.id);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const purchaseReturn = await this.purchasesApi.createReturn(order.id, payload);

      this.successMessage.set(
        `La devolucion ${purchaseReturn.public_id || '#' + purchaseReturn.id} fue registrada por ${this.formatCurrency(purchaseReturn.return_total)}.`,
      );

      await Promise.all([this.loadOrders(), this.loadProducts()]);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.returningOrderId.set(null);
    }
  }

  protected resetForm(resetSelection = true): void {
    this.editingOrderId.set(null);
    this.purchaseCancellationReason.set('');
    this.form.reset({
      supplier_id: '',
      reference: '',
      ordered_at: this.todayDateInput(),
      notes: '',
    });
    this.itemsArray.clear();
    this.itemsArray.push(this.createItemGroup());

    if (resetSelection) {
      const nextOrder = this.orders()[0] ?? null;
      this.selectedOrderId.set(nextOrder?.id ?? null);
      this.resetPurchasePaymentForm(nextOrder);
      this.resetPurchaseReturnForm(nextOrder);
    }
  }

  protected addItemRow(): void {
    this.itemsArray.push(this.createItemGroup());
  }

  protected removeItemRow(index: number): void {
    if (this.itemsArray.length <= 1) {
      return;
    }

    this.itemsArray.removeAt(index);
  }

  protected onProductChange(index: number, value: string | number | null): void {
    const product = this.products().find((item) => item.id === Number(value)) ?? null;

    if (!product) {
      return;
    }

    this.asFormGroup(this.itemControls()[index]).patchValue({
      unit_cost: product.cost_price,
    });
  }

  protected itemControls(): AbstractControl[] {
    return this.itemsArray.controls;
  }

  protected asFormGroup(control: AbstractControl): FormGroup {
    return control as FormGroup;
  }

  protected productForRow(index: number): Product | null {
    const productId = Number(this.asFormGroup(this.itemControls()[index]).getRawValue().product_id || 0);
    return this.products().find((product) => product.id === productId) ?? null;
  }

  protected formTotal(): number {
    return this.itemsArray.controls.reduce((total, control) => {
      const value = this.asFormGroup(control).getRawValue();
      return total + Number(value.quantity_ordered || 0) * Number(value.unit_cost || 0);
    }, 0);
  }

  protected labelForStatus(status: PurchaseOrderStatus): string {
    switch (status) {
      case 'received':
        return 'Recibida';
      case 'ordered':
        return 'Pendiente';
      case 'cancelled':
        return 'Anulada';
      default:
        return status;
    }
  }

  protected labelForPaymentStatus(status: PurchasePaymentStatus): string {
    switch (status) {
      case 'partial':
        return 'Pago parcial';
      case 'paid':
        return 'Pagada';
      case 'credit':
        return 'Credito a favor';
      case 'pending':
      default:
        return 'Pendiente de pago';
    }
  }

  protected formatPaymentMethod(method: PurchasePaymentMethod): string {
    switch (method) {
      case 'cash':
        return 'Efectivo';
      case 'card':
        return 'Tarjeta';
      case 'check':
        return 'Cheque';
      case 'transfer':
      default:
        return 'Transferencia';
    }
  }

  protected estimatedBalanceAfterPayment(order: PurchaseOrder): number {
    return this.roundCurrency(order.balance_due - this.purchasePaymentAmount());
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

    return this.dateFormatter.format(new Date(value));
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return 'Sin fecha';
    }

    return this.dateTimeFormatter.format(new Date(value));
  }

  private get itemsArray(): FormArray {
    return this.form.get('items') as FormArray;
  }

  private createItemGroup(): FormGroup {
    return this.fb.group({
      product_id: ['', [Validators.required]],
      quantity_ordered: [1, [Validators.required, Validators.min(0.01)]],
      unit_cost: [0, [Validators.required, Validators.min(0)]],
      notes: [''],
    });
  }

  private applyLegacyPrefillFromQuery(): void {
    const query = this.route.snapshot.queryParamMap;
    const supplierName = this.nullableText(query.get('supplier_name'));
    const orderedAt = this.normalizeLegacyDate(query.get('ordered_at'));
    const legacyNote = this.buildLegacyPurchaseNote(
      this.nullableText(query.get('legacy_note')),
      this.nullableText(query.get('legacy_total')),
    );

    if (supplierName === null && orderedAt === null && legacyNote === null) {
      return;
    }

    const matchedSupplier =
      supplierName === null
        ? null
        : this.suppliers().find(
            (supplier) => supplier.name.trim().toLowerCase() === supplierName.toLowerCase(),
          ) ?? null;

    this.resetForm(false);
    this.form.patchValue({
      supplier_id: matchedSupplier?.id ? String(matchedSupplier.id) : '',
      ordered_at: orderedAt ?? this.form.getRawValue().ordered_at,
      notes: legacyNote ?? '',
    });

    if (supplierName !== null && matchedSupplier === null) {
      this.legacyNotice.set(
        this.mergeLegacyNotice(
          this.legacyNotice(),
          `No encontramos el proveedor legacy "${supplierName}". Seleccionalo manualmente antes de guardar la nueva orden.`,
        ),
      );
    }
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch ((value ?? '').trim()) {
      case 'company-orders':
        return 'El antiguo modulo Pedido de empresa fue absorbido por Compras. Ahora las ordenes se registran con items detallados y stock real.';
      case 'company-orders-write':
        return 'El formulario legacy de pedidos ya no guarda registros. Revise los datos precargados y reconstruya la orden en el flujo nuevo.';
      case 'company-orders-delete':
        return 'La eliminacion legacy fue retirada. Usa el estado y las anulaciones del modulo Compras para cerrar ordenes.';
      default:
        return null;
    }
  }

  private mergeLegacyNotice(base: string | null, extra: string): string {
    const normalizedBase = base?.trim() ?? '';

    if (normalizedBase === '') {
      return extra;
    }

    return `${normalizedBase} ${extra}`;
  }

  private normalizeLegacyDate(value: string | null): string | null {
    const normalized = (value ?? '').trim();

    if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
      return normalized;
    }

    return null;
  }

  private buildLegacyPurchaseNote(description: string | null, total: string | null): string | null {
    const parts: string[] = [];

    if (description !== null) {
      parts.push(`Pedido legacy: ${description}`);
    }

    if (total !== null) {
      parts.push(`Monto referencial legacy: ${total}`);
    }

    return parts.length === 0 ? null : parts.join(' · ');
  }

  private applyOrderToForm(order: PurchaseOrder): void {
    this.form.patchValue({
      supplier_id: String(order.supplier?.id ?? ''),
      reference: order.reference ?? '',
      ordered_at: this.toDateInput(order.ordered_at),
      notes: order.notes ?? '',
    });

    this.itemsArray.clear();

    for (const item of order.items) {
      this.itemsArray.push(
        this.fb.group({
          product_id: String(item.product_id ?? ''),
          quantity_ordered: item.quantity_ordered,
          unit_cost: item.unit_cost,
          notes: item.notes ?? '',
        }),
      );
    }

    if (this.itemsArray.length === 0) {
      this.itemsArray.push(this.createItemGroup());
    }
  }

  private buildPayload(): PurchaseOrderPayload {
    const raw = this.form.getRawValue();

    return {
      supplier_id: Number(raw.supplier_id),
      reference: this.nullableText(raw.reference),
      ordered_at: raw.ordered_at || null,
      notes: this.nullableText(raw.notes),
      items: this.itemsArray.controls.map((control) => {
        const item = this.asFormGroup(control).getRawValue();

        return {
          product_id: Number(item.product_id),
          quantity_ordered: Number(item.quantity_ordered),
          unit_cost: Number(item.unit_cost),
          notes: this.nullableText(item.notes),
        };
      }),
    };
  }

  private buildPurchaseReturnPayload(): CreatePurchaseReturnPayload | null {
    const reason = this.nullableText(this.purchaseReturnReason());

    if (!reason) {
      this.error.set('Debes registrar un motivo antes de devolver mercaderia.');
      return null;
    }

    const items = this.selectedReturnEntries().map((entry) => ({
      purchase_order_item_id: entry.item.id,
      quantity: entry.quantity,
      reason: null,
    }));

    if (items.length === 0) {
      this.error.set('Selecciona al menos una cantidad pendiente antes de registrar la devolucion.');
      return null;
    }

    return {
      returned_at: new Date().toISOString(),
      reason,
      notes: this.nullableText(this.purchaseReturnNotes()),
      items,
    };
  }

  private buildPurchasePaymentPayload(order: PurchaseOrder): CreatePurchaseOrderPaymentPayload | null {
    const amount = this.normalizePaymentAmount(this.purchasePaymentAmount());

    if (amount <= 0) {
      this.error.set('Debes registrar un monto valido antes de guardar el pago.');
      return null;
    }

    if (amount > order.balance_due) {
      this.error.set('El monto no puede superar el saldo pendiente de la compra.');
      return null;
    }

    return {
      method: this.purchasePaymentMethod(),
      amount,
      reference: this.nullableText(this.purchasePaymentReference()),
      notes: this.nullableText(this.purchasePaymentNotes()),
      paid_at: new Date().toISOString(),
    };
  }

  private resetPurchasePaymentForm(order: PurchaseOrder | null = null): void {
    this.purchasePaymentMethod.set('transfer');
    this.purchasePaymentAmount.set(this.normalizePaymentAmount(Math.max(0, order?.balance_due ?? 0)));
    this.purchasePaymentReference.set('');
    this.purchasePaymentNotes.set('');
  }

  private resetPurchaseReturnForm(order: PurchaseOrder | null = null): void {
    this.purchaseReturnReason.set('Devolucion parcial registrada desde la nueva plataforma.');
    this.purchaseReturnNotes.set('');
    this.purchaseReturnQuantities.set(
      Object.fromEntries((order?.items ?? []).map((item) => [item.id, 0])),
    );
  }

  private normalizeReturnQuantity(value: number, max: number): number {
    if (!Number.isFinite(value) || value <= 0) {
      return 0;
    }

    return this.roundCurrency(Math.min(value, max));
  }

  private normalizePaymentAmount(value: number): number {
    if (!Number.isFinite(value) || value <= 0) {
      return 0;
    }

    return this.roundCurrency(value);
  }

  protected roundCurrency(value: number): number {
    return Math.round(value * 100) / 100;
  }

  private nullableText(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }

  private todayDateInput(): string {
    return this.toDateInput(new Date().toISOString());
  }

  private toDateInput(value: string | Date | null): string {
    if (!value) {
      return this.todayDateInput();
    }

    const date = value instanceof Date ? value : new Date(value);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }
}
