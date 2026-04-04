import { Component, computed, inject, signal } from '@angular/core';
import { AbstractControl, FormArray, FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatProgressBarModule } from '@angular/material/progress-bar';
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
  imports: [ReactiveFormsModule, MatProgressBarModule],
  template: `
    <section class="stack purchases-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Compras</span>
          <h1 class="page-title">Abastecimiento, recepcion y cuentas por pagar.</h1>
          <p class="page-description">
            El tablero principal queda orientado a seguimiento. Las ordenes, pagos, devoluciones y
            anulaciones se gestionan en flujos separados para evitar una pantalla saturada.
          </p>
        </div>
        <span class="pill">Operacion de compras</span>
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
          <div class="field field--search purchases-toolbar__search">
            <label for="purchase-search">Buscar orden o proveedor</label>
            <input
              id="purchase-search"
              type="text"
              [value]="search()"
              (input)="onSearch($event)"
              placeholder="OC, proveedor o documento"
            />
          </div>

          <div class="field purchases-toolbar__filter">
            <label for="purchase-status">Estado</label>
            <select id="purchase-status" #purchaseStatus [value]="statusFilter()" (change)="onStatusChange(purchaseStatus.value)">
              <option value="">Todos</option>
              <option value="ordered">Pendiente</option>
              <option value="received">Recibida</option>
              <option value="cancelled">Anulada</option>
            </select>
          </div>

          <div class="cta-row">
            <button class="btn" type="button" (click)="refresh()">Refrescar</button>
            @if (auth.isAdmin()) {
              <button class="btn btn--primary" type="button" (click)="openCreateOrderDialog()">
                Nueva orden
              </button>
            }
          </div>
        </div>
      </article>

      <section class="purchases-layout">
        <article class="surface stack">
          <div class="purchases-block__header">
            <div>
              <span class="page-kicker">Ordenes de compra</span>
              <h2 class="purchases-section__title">Registro operativo</h2>
            </div>
          </div>

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
                        <div class="badge-row">
                          <span
                            class="pill"
                            [class.purchase-pill-received]="order.status === 'received'"
                            [class.purchase-pill-cancelled]="order.status === 'cancelled'"
                          >
                            {{ labelForStatus(order.status) }}
                          </span>
                          <span
                            class="pill"
                            [class.purchase-payment-pill-partial]="order.payment_status === 'partial'"
                            [class.purchase-payment-pill-paid]="order.payment_status === 'paid'"
                            [class.purchase-payment-pill-credit]="order.payment_status === 'credit'"
                          >
                            {{ labelForPaymentStatus(order.payment_status) }}
                          </span>
                          <span class="pill pill--muted">{{ order.items_count }} item(s)</span>
                        </div>
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
                      <button class="btn btn--ghost" type="button" (click)="selectOnly(order, $event)">
                        Ver
                      </button>
                      @if (auth.isAdmin() && order.status === 'ordered') {
                        <button class="btn btn--ghost" type="button" (click)="editOrder(order, $event)">
                          Editar
                        </button>
                        <button
                          class="btn btn--primary"
                          type="button"
                          (click)="receiveOrder(order, $event)"
                          [disabled]="receivingId() === order.id"
                        >
                          Recibir
                        </button>
                      }
                      @if (auth.isAdmin() && order.status !== 'cancelled') {
                        <button
                          class="btn"
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
        </article>

        <div class="purchases-side">
          <article class="surface stack">
            <div class="purchases-block__header">
              <div>
                <span class="page-kicker">Detalle</span>
                <h2 class="purchases-section__title">Seguimiento de la orden</h2>
              </div>
              <div class="cta-row">
                @if (selectedOrder(); as order) {
                  @if (auth.isAdmin() && order.status === 'ordered') {
                    <button class="btn btn--ghost" type="button" (click)="editOrder(order, $event)">
                      Editar
                    </button>
                    <button
                      class="btn"
                      type="button"
                      (click)="receiveOrder(order, $event)"
                      [disabled]="receivingId() === order.id"
                    >
                      {{ receivingId() === order.id ? 'Recibiendo...' : 'Recibir' }}
                    </button>
                  }
                  @if (auth.isAdmin() && order.status === 'received') {
                    <button class="btn btn--ghost" type="button" (click)="openPaymentDialog(order)">
                      Registrar pago
                    </button>
                    <button class="btn btn--ghost" type="button" (click)="openReturnDialog(order)">
                      Devolucion
                    </button>
                  }
                  @if (auth.isAdmin() && order.status !== 'cancelled') {
                    <button class="btn" type="button" (click)="prepareCancellation(order, $event)">
                      Anular
                    </button>
                  }
                }
              </div>
            </div>

              @if (selectedOrder(); as order) {
                <div class="stack">
                  <div class="badge-row">
                    <span class="pill">{{ labelForStatus(order.status) }}</span>
                    <span
                      class="pill"
                      [class.purchase-payment-pill-partial]="order.payment_status === 'partial'"
                      [class.purchase-payment-pill-paid]="order.payment_status === 'paid'"
                      [class.purchase-payment-pill-credit]="order.payment_status === 'credit'"
                    >
                      {{ labelForPaymentStatus(order.payment_status) }}
                    </span>
                    <span class="pill pill--muted">{{ formatDate(order.ordered_at) }}</span>
                    <span class="pill pill--muted">
                      Proveedor {{ order.supplier?.name || 'Sin proveedor' }}
                    </span>
                    @if (order.returns_count > 0) {
                      <span class="pill pill--muted">
                        Devuelto {{ formatCurrency(order.returned_total) }}
                      </span>
                    }
                    @if (order.payments_count > 0) {
                      <span class="pill pill--muted">Pagado {{ formatCurrency(order.paid_total) }}</span>
                    }
                    <span class="pill pill--muted">Saldo {{ formatCurrency(order.balance_due) }}</span>
                    @if (order.cancelled_at) {
                      <span class="pill pill--muted">Anulada {{ formatDateTime(order.cancelled_at) }}</span>
                    }
                  </div>

                  <div class="purchase-order-meta">
                    <strong>{{ order.reference || order.public_id || '#' + order.id }}</strong>
                    <span>
                      Total {{ formatCurrency(order.grand_total) }}
                      @if (order.received_at) {
                        · Recibida {{ formatDateTime(order.received_at) }}
                      }
                    </span>
                  </div>

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

                  <div class="purchase-summary-grid">
                    <article class="surface surface--muted purchase-summary-card">
                      <span class="metric-card__label">Neto a pagar</span>
                      <strong>{{ formatCurrency(order.net_payable_total) }}</strong>
                      <small>Total despues de devoluciones.</small>
                    </article>

                    <article class="surface surface--muted purchase-summary-card">
                      <span class="metric-card__label">Pagado</span>
                      <strong>{{ formatCurrency(order.paid_total) }}</strong>
                      <small>{{ order.payments_count }} pago(s) registrados.</small>
                    </article>

                    <article class="surface surface--muted purchase-summary-card">
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

                  <div class="stack">

                    @if (order.payments.length === 0) {
                      <p class="muted">Esta orden todavia no tiene pagos registrados.</p>
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
                              <div class="badge-row">
                                <span class="pill pill--muted">
                                  Total {{ formatCurrency(purchaseReturn.return_total) }}
                                </span>
                                @for (item of purchaseReturn.items; track item.id) {
                                  <span class="pill pill--muted">
                                    {{ item.name_snapshot }} · {{ formatNumber(item.quantity) }}
                                  </span>
                                }
                              </div>
                            </div>
                            <div class="purchase-detail-item__amount">
                              {{ formatCurrency(purchaseReturn.return_total) }}
                            </div>
                          </article>
                        }
                      </div>
                    }
                  </div>
                </div>
              } @else {
                <p class="muted">Aqui veras el detalle, los items y el estado de la orden seleccionada.</p>
              }
          </article>

          @if (!auth.isAdmin()) {
            <article class="surface stack">
              <span class="page-kicker">Permisos</span>
              <h2 class="purchases-section__title">Consulta habilitada</h2>
              <p class="muted">
                Tu rol puede revisar ordenes y recepciones, pero la creacion o edicion se reserva a
                administracion.
              </p>
            </article>
          }
        </div>
      </section>

      @if (auth.isAdmin() && orderDialogOpen()) {
        <div class="purchases-modal-backdrop" (click)="closeOrderDialog()"></div>
        <section class="purchases-modal" role="dialog" aria-modal="true">
          <article class="surface purchases-modal__panel purchases-modal__panel--wide">
            <header class="purchases-modal__header">
              <div class="page-header__copy">
                <span class="page-kicker">Orden de compra</span>
                <h2 class="page-title">
                  {{ editingOrderId() ? 'Editar orden de compra' : 'Nueva orden de compra' }}
                </h2>
                <p class="page-description">
                  Divide la preparacion de la compra por pasos para que el alta no invada el resto de la vista.
                </p>
              </div>
              <button class="btn btn--ghost" type="button" (click)="closeOrderDialog()">Cerrar</button>
            </header>

            <div class="purchases-steps">
              <button
                class="purchases-step"
                type="button"
                [class.is-active]="orderStep() === 'general'"
                (click)="setOrderStep('general')"
              >
                General
              </button>
              <button
                class="purchases-step"
                type="button"
                [class.is-active]="orderStep() === 'items'"
                (click)="setOrderStep('items')"
              >
                Items
              </button>
            </div>

            <form class="form-grid" [formGroup]="form">
              @if (orderStep() === 'general') {
                <div class="split">
                  <div class="field">
                    <label for="purchase-supplier">Proveedor</label>
                    <select id="purchase-supplier" formControlName="supplier_id">
                      <option value="">Selecciona un proveedor</option>
                      @for (supplier of suppliers(); track supplier.id) {
                        <option [value]="supplier.id">{{ supplier.name }}</option>
                      }
                    </select>
                  </div>

                  <div class="field">
                    <label for="purchase-reference">Referencia</label>
                    <input id="purchase-reference" type="text" formControlName="reference" />
                  </div>
                </div>

                <div class="split">
                  <div class="field">
                    <label for="purchase-ordered-at">Fecha de orden</label>
                    <input id="purchase-ordered-at" type="date" formControlName="ordered_at" />
                  </div>

                  <div class="field">
                    <label for="purchase-notes">Notas</label>
                    <textarea id="purchase-notes" formControlName="notes"></textarea>
                  </div>
                </div>
              }

              @if (orderStep() === 'items') {
                <div class="purchases-items-head">
                  <div>
                    <span class="page-kicker">Items</span>
                    <h3 class="purchases-section__title purchases-form__title">Detalle de compra</h3>
                  </div>
                  <button class="btn btn--ghost" type="button" (click)="addItemRow()">Agregar item</button>
                </div>

                <div class="purchase-form-items">
                  @for (itemGroup of itemControls(); track $index; let index = $index) {
                    <article class="purchase-form-item" [formGroup]="asFormGroup(itemGroup)">
                      <div class="purchase-form-item__grid">
                        <div class="field">
                          <label [for]="'purchase-item-product-' + index">Producto</label>
                          <select
                            [id]="'purchase-item-product-' + index"
                            formControlName="product_id"
                            #purchaseProduct
                            (change)="onProductChange(index, purchaseProduct.value)"
                          >
                            <option value="">Selecciona un producto</option>
                            @for (product of products(); track product.id) {
                              <option [value]="product.id">
                                {{ product.name }} · Costo {{ formatCurrency(product.cost_price) }}
                              </option>
                            }
                          </select>
                        </div>

                        <div class="field">
                          <label [for]="'purchase-item-qty-' + index">Cantidad</label>
                          <input
                            [id]="'purchase-item-qty-' + index"
                            type="number"
                            min="0.01"
                            step="0.01"
                            formControlName="quantity_ordered"
                          />
                        </div>

                        <div class="field">
                          <label [for]="'purchase-item-cost-' + index">Costo unitario</label>
                          <input
                            [id]="'purchase-item-cost-' + index"
                            type="number"
                            min="0"
                            step="0.01"
                            formControlName="unit_cost"
                          />
                        </div>
                      </div>

                      <div class="purchase-form-item__meta">
                        @if (productForRow(index); as product) {
                          <div class="badge-row">
                            <span class="pill pill--muted">Stock {{ formatNumber(product.current_stock) }}</span>
                            <span class="pill pill--muted">Unidad {{ product.unit }}</span>
                            <span class="pill pill--muted">Venta {{ formatCurrency(product.sale_price) }}</span>
                          </div>
                        }

                        <button
                          class="btn"
                          type="button"
                          (click)="removeItemRow(index)"
                          [disabled]="itemControls().length === 1"
                        >
                          Eliminar linea
                        </button>
                      </div>

                      <div class="field">
                        <label [for]="'purchase-item-note-' + index">Nota del item</label>
                        <input [id]="'purchase-item-note-' + index" type="text" formControlName="notes" />
                      </div>
                    </article>
                  }
                </div>

                <div class="badge-row">
                  <span class="pill pill--muted">{{ itemControls().length }} linea(s)</span>
                  <span class="pill">Total estimado {{ formatCurrency(formTotal()) }}</span>
                </div>
              }

              @if (error()) {
                <p class="alert alert--danger">{{ error() }}</p>
              }

              <div class="cta-row purchases-modal__actions">
                <button class="btn btn--ghost" type="button" (click)="closeOrderDialog()">Cancelar</button>
                <button
                  class="btn btn--ghost"
                  type="button"
                  (click)="previousOrderStep()"
                  [disabled]="orderStep() === 'general'"
                >
                  Atras
                </button>
                @if (orderStep() === 'general') {
                  <button class="btn" type="button" (click)="nextOrderStep()">Siguiente</button>
                } @else {
                  <button class="btn" type="button" (click)="resetForm(false)">Limpiar</button>
                  <button class="btn btn--primary" type="button" (click)="save()" [disabled]="saving()">
                    @if (saving()) {
                      Guardando...
                    } @else if (editingOrderId()) {
                      Actualizar orden
                    } @else {
                      Crear orden
                    }
                  </button>
                }
              </div>
            </form>
          </article>
        </section>
      }

      @if (auth.isAdmin() && paymentDialogOpen() && selectedOrder(); as order) {
        <div class="purchases-modal-backdrop" (click)="closePaymentDialog()"></div>
        <section class="purchases-modal" role="dialog" aria-modal="true">
          <article class="surface purchases-modal__panel">
            <header class="purchases-modal__header">
              <div class="page-header__copy">
                <span class="page-kicker">Pago</span>
                <h2 class="page-title">Registrar pago a proveedor</h2>
                <p class="page-description">
                  {{ order.reference || order.public_id || '#' + order.id }} · saldo actual
                  {{ formatCurrency(order.balance_due) }}
                </p>
              </div>
              <button class="btn btn--ghost" type="button" (click)="closePaymentDialog()">Cerrar</button>
            </header>

            @if (order.balance_due <= 0) {
              <p class="muted">Esta compra ya no tiene saldo pendiente para nuevos pagos.</p>
            } @else {
              <div class="form-grid">
                <div class="split">
                  <div class="field">
                    <label for="purchase-payment-method">Metodo</label>
                    <select
                      id="purchase-payment-method"
                      #purchasePaymentMethodInput
                      [value]="purchasePaymentMethod()"
                      (change)="onPurchasePaymentMethodChange(purchasePaymentMethodInput.value)"
                    >
                      @for (paymentMethod of paymentMethods; track paymentMethod.value) {
                        <option [value]="paymentMethod.value">{{ paymentMethod.label }}</option>
                      }
                    </select>
                  </div>

                  <div class="field">
                    <label for="purchase-payment-amount">Monto</label>
                    <input
                      id="purchase-payment-amount"
                      type="number"
                      min="0.01"
                      step="0.01"
                      [value]="purchasePaymentAmount()"
                      [disabled]="payingOrderId() === order.id"
                      (input)="onPurchasePaymentAmountChange($event)"
                    />
                  </div>
                </div>

                <div class="split">
                  <div class="field">
                    <label for="purchase-payment-reference">Referencia</label>
                    <input
                      id="purchase-payment-reference"
                      type="text"
                      [value]="purchasePaymentReference()"
                      [disabled]="payingOrderId() === order.id"
                      (input)="onPurchasePaymentReferenceChange($event)"
                    />
                  </div>

                  <div class="field">
                    <label for="purchase-payment-estimate">Nuevo saldo estimado</label>
                    <input
                      id="purchase-payment-estimate"
                      type="text"
                      [value]="formatCurrency(estimatedBalanceAfterPayment(order))"
                      readonly
                    />
                  </div>
                </div>

                <div class="field">
                  <label for="purchase-payment-notes">Notas internas</label>
                  <textarea
                    id="purchase-payment-notes"
                    [value]="purchasePaymentNotes()"
                    [disabled]="payingOrderId() === order.id"
                    (input)="onPurchasePaymentNotesChange($event)"
                  ></textarea>
                </div>

                @if (purchasePaymentMethod() === 'cash') {
                  <p class="muted purchases-warning-text">
                    Los pagos en efectivo requieren una caja abierta para el usuario actual.
                  </p>
                }

                <div class="badge-row">
                  <span class="pill pill--muted">Saldo actual {{ formatCurrency(order.balance_due) }}</span>
                  <span class="pill">Nuevo saldo {{ formatCurrency(estimatedBalanceAfterPayment(order)) }}</span>
                </div>

                <div class="cta-row purchases-modal__actions">
                  <button class="btn btn--ghost" type="button" (click)="closePaymentDialog()">Cancelar</button>
                  <button class="btn" type="button" (click)="setOutstandingPaymentAmount(order)">
                    Usar saldo pendiente
                  </button>
                  <button
                    class="btn btn--primary"
                    type="button"
                    (click)="createPurchasePayment()"
                    [disabled]="isPaymentDisabled(order)"
                  >
                    {{ payingOrderId() === order.id ? 'Guardando...' : 'Registrar pago' }}
                  </button>
                </div>
              </div>
            }
          </article>
        </section>
      }

      @if (auth.isAdmin() && returnDialogOpen() && selectedOrder(); as order) {
        <div class="purchases-modal-backdrop" (click)="closeReturnDialog()"></div>
        <section class="purchases-modal" role="dialog" aria-modal="true">
          <article class="surface purchases-modal__panel purchases-modal__panel--wide">
            <header class="purchases-modal__header">
              <div class="page-header__copy">
                <span class="page-kicker">Devolucion</span>
                <h2 class="page-title">Registrar devolucion a proveedor</h2>
                <p class="page-description">
                  Selecciona cantidades pendientes por item y deja un motivo general.
                </p>
              </div>
              <button class="btn btn--ghost" type="button" (click)="closeReturnDialog()">Cerrar</button>
            </header>

            @if (!hasReturnableItems(order)) {
              <p class="muted">Todos los items recibidos ya fueron devueltos por completo.</p>
            } @else {
              <div class="form-grid">
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
                          <div class="field">
                            <label [for]="'purchase-return-' + item.id">Cantidad a devolver</label>
                            <input
                              [id]="'purchase-return-' + item.id"
                              type="number"
                              min="0"
                              [max]="item.remaining_returnable_quantity"
                              step="0.01"
                              [value]="getReturnQuantity(item.id)"
                              [disabled]="returningOrderId() === order.id"
                              (input)="onReturnQuantityChange(item, $event)"
                            />
                          </div>

                          <button
                            class="btn"
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

                <div class="field">
                  <label for="purchase-return-reason">Motivo general</label>
                  <textarea
                    id="purchase-return-reason"
                    [value]="purchaseReturnReason()"
                    (input)="onPurchaseReturnReasonChange($event)"
                  ></textarea>
                </div>

                <div class="field">
                  <label for="purchase-return-notes">Notas internas</label>
                  <textarea
                    id="purchase-return-notes"
                    [value]="purchaseReturnNotes()"
                    (input)="onPurchaseReturnNotesChange($event)"
                  ></textarea>
                </div>

                <div class="badge-row">
                  <span class="pill pill--muted">
                    Items seleccionados {{ selectedReturnEntries().length }}
                  </span>
                  <span class="pill">Total devuelto {{ formatCurrency(selectedReturnTotal()) }}</span>
                </div>

                <div class="cta-row purchases-modal__actions">
                  <button class="btn btn--ghost" type="button" (click)="closeReturnDialog()">Cancelar</button>
                  <button
                    class="btn btn--primary"
                    type="button"
                    (click)="createPurchaseReturn()"
                    [disabled]="isPurchaseReturnDisabled(order)"
                  >
                    {{ returningOrderId() === order.id ? 'Guardando...' : 'Registrar devolucion' }}
                  </button>
                </div>
              </div>
            }
          </article>
        </section>
      }

      @if (auth.isAdmin() && cancellationDialogOpen() && selectedOrder(); as order) {
        <div class="purchases-modal-backdrop" (click)="closeCancellationDialog()"></div>
        <section class="purchases-modal" role="dialog" aria-modal="true">
          <article class="surface purchases-modal__panel">
            <header class="purchases-modal__header">
              <div class="page-header__copy">
                <span class="page-kicker">Anulacion</span>
                <h2 class="page-title">Anular orden</h2>
                <p class="page-description">
                  {{ order.reference || order.public_id || '#' + order.id }} ·
                  {{ labelForStatus(order.status) }}
                </p>
              </div>
              <button class="btn btn--ghost" type="button" (click)="closeCancellationDialog()">
                Cerrar
              </button>
            </header>

            <div class="form-grid">
              @if (order.payments_count > 0) {
                <p class="muted purchases-warning-text">
                  La anulacion queda bloqueada porque la compra ya tiene pagos registrados.
                </p>
              }

              <div class="field">
                <label for="purchase-cancellation-reason">Motivo de anulacion</label>
                <textarea
                  id="purchase-cancellation-reason"
                  [value]="purchaseCancellationReason()"
                  (input)="onPurchaseCancellationReasonChange($event)"
                ></textarea>
              </div>

              @if (order.status === 'received') {
                <p class="muted purchases-warning-text">
                  Esta accion revertira la entrada de stock de la compra, siempre que el inventario actual alcance para hacer la reversa.
                </p>
              }

              <div class="cta-row purchases-modal__actions">
                <button class="btn btn--ghost" type="button" (click)="closeCancellationDialog()">
                  Cancelar
                </button>
                <button
                  class="btn"
                  type="button"
                  (click)="cancelSelectedOrder()"
                  [disabled]="cancellingId() === order.id || order.payments_count > 0"
                >
                  {{ cancellingId() === order.id ? 'Procesando...' : 'Confirmar anulacion' }}
                </button>
              </div>
            </div>
          </article>
        </section>
      }
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

    .purchases-block__header,
    .purchases-modal__header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .purchases-section__title {
      margin: 0.2rem 0 0;
      color: var(--text-soft);
      font-size: 1.35rem;
      line-height: 1.15;
      letter-spacing: -0.03em;
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
      align-items: end;
    }

    .purchases-toolbar__search {
      min-width: min(24rem, 100%);
      flex: 1 1 20rem;
    }

    .purchases-toolbar__filter {
      min-width: 12rem;
    }

    .purchase-order-list,
    .purchase-detail-items,
    .purchase-form-items,
    .purchase-payment-history,
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
      border: 1px solid rgba(148, 163, 184, 0.18);
      border-radius: 1.25rem;
      background: rgba(246, 249, 252, 0.88);
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
      border-color: rgba(22, 138, 87, 0.26);
      background: rgba(22, 138, 87, 0.06);
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

    .purchase-order-meta {
      display: grid;
      gap: 0.25rem;
    }

    .purchase-order-meta span {
      color: var(--text-muted);
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

    .purchase-summary-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
      display: grid;
      gap: 0.85rem;
    }

    .purchase-summary-card {
      display: grid;
      gap: 0.25rem;
      border-radius: 1rem;
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

    .purchase-pill-received {
      background: rgba(19, 128, 77, 0.14);
      color: #166534;
    }

    .purchase-pill-cancelled {
      background: rgba(148, 28, 28, 0.14);
      color: #991b1b;
    }

    .purchase-payment-pill-partial {
      background: rgba(180, 83, 9, 0.14);
      color: #92400e;
    }

    .purchase-payment-pill-paid {
      background: rgba(19, 128, 77, 0.14);
      color: #166534;
    }

    .purchase-payment-pill-credit {
      background: rgba(15, 76, 129, 0.14);
      color: #0f4c81;
    }

    .purchases-warning-text {
      color: var(--text-muted);
    }

    .purchases-modal-backdrop {
      position: fixed;
      inset: 0;
      z-index: 60;
      background: rgba(9, 14, 25, 0.42);
    }

    .purchases-modal {
      position: fixed;
      inset: 0;
      z-index: 61;
      display: grid;
      place-items: center;
      padding: 1.25rem;
    }

    .purchases-modal__panel {
      width: min(100%, 44rem);
      max-height: calc(100vh - 2.5rem);
      overflow: auto;
    }

    .purchases-modal__panel--wide {
      width: min(100%, 68rem);
    }

    .purchases-modal__actions {
      justify-content: flex-end;
    }

    .purchases-steps {
      display: flex;
      flex-wrap: wrap;
      gap: 0.65rem;
      margin-bottom: 1rem;
    }

    .purchases-step {
      border: 1px solid rgba(148, 163, 184, 0.22);
      border-radius: 999px;
      background: rgba(246, 249, 252, 0.95);
      color: var(--text-muted);
      font-weight: 700;
      min-height: 2.7rem;
      padding: 0 1rem;
    }

    .purchases-step.is-active {
      border-color: rgba(22, 138, 87, 0.26);
      background: rgba(22, 138, 87, 0.1);
      color: var(--primary-strong);
    }

    @media (max-width: 1180px) {
      .purchases-layout,
      .purchase-summary-grid,
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
  protected readonly orderDialogOpen = signal(false);
  protected readonly paymentDialogOpen = signal(false);
  protected readonly returnDialogOpen = signal(false);
  protected readonly cancellationDialogOpen = signal(false);
  protected readonly orderStep = signal<'general' | 'items'>('general');
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

  protected openCreateOrderDialog(): void {
    this.resetForm(false);
    this.orderStep.set('general');
    this.orderDialogOpen.set(true);
  }

  protected closeOrderDialog(): void {
    this.orderDialogOpen.set(false);
    this.orderStep.set('general');
    this.resetForm(false);
  }

  protected setOrderStep(step: 'general' | 'items'): void {
    this.orderStep.set(step);
  }

  protected nextOrderStep(): void {
    this.orderStep.set('items');
  }

  protected previousOrderStep(): void {
    this.orderStep.set('general');
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
    this.orderStep.set('general');
    this.orderDialogOpen.set(true);
    this.purchaseCancellationReason.set(order.cancellation_reason ?? '');
    this.resetPurchasePaymentForm(order);
    this.resetPurchaseReturnForm(order);
    this.applyOrderToForm(order);
  }

  protected prepareCancellation(order: PurchaseOrder, event: Event): void {
    event.stopPropagation();
    this.selectOrder(order);
    this.cancellationDialogOpen.set(true);

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
      this.orderDialogOpen.set(false);
      this.orderStep.set('general');
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

  protected onPurchasePaymentMethodChange(value: PurchasePaymentMethod | string | null): void {
    this.purchasePaymentMethod.set((value as PurchasePaymentMethod) ?? 'transfer');
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

      this.paymentDialogOpen.set(false);
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
      this.cancellationDialogOpen.set(false);
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

      this.returnDialogOpen.set(false);
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

  protected openPaymentDialog(order: PurchaseOrder): void {
    this.selectOrder(order);
    this.paymentDialogOpen.set(true);
  }

  protected closePaymentDialog(): void {
    this.paymentDialogOpen.set(false);
    this.resetPurchasePaymentForm(this.selectedOrder());
  }

  protected openReturnDialog(order: PurchaseOrder): void {
    this.selectOrder(order);
    this.returnDialogOpen.set(true);
  }

  protected closeReturnDialog(): void {
    this.returnDialogOpen.set(false);
    this.resetPurchaseReturnForm(this.selectedOrder());
  }

  protected closeCancellationDialog(): void {
    this.cancellationDialogOpen.set(false);
    this.purchaseCancellationReason.set(this.selectedOrder()?.cancellation_reason ?? '');
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
