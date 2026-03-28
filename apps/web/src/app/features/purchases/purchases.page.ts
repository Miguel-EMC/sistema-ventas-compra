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
import { AuthService } from '../../core/auth/auth.service';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { SuppliersApiService } from '../suppliers/suppliers.api';
import { BusinessPartner } from '../partners/partners.types';
import { ProductsApiService } from '../products/products.api';
import { Product } from '../products/products.types';
import { PurchasesApiService } from './purchases.api';
import { PurchaseOrder, PurchaseOrderPayload, PurchaseOrderStatus } from './purchases.types';

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
            Este modulo ya conecta proveedores, productos y movimientos de ingreso para registrar
            compras reales sobre la API nueva.
          </p>
        </div>
        <span class="pill">Compras + stock trazable</span>
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
          <span class="metric-card__label">Monto total</span>
          <strong class="metric-card__value">{{ formatCurrency(totalPurchased()) }}</strong>
          <span class="metric-card__hint">Suma de ordenes listadas en el filtro actual.</span>
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
                          <mat-chip [class.purchase-chip-received]="order.status === 'received'">
                            {{ labelForStatus(order.status) }}
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
                    <mat-chip>{{ formatDate(order.ordered_at) }}</mat-chip>
                    <mat-chip>Proveedor {{ order.supplier?.name || 'Sin proveedor' }}</mat-chip>
                  </mat-chip-set>

                  @if (order.notes) {
                    <p class="muted">{{ order.notes }}</p>
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
                      @if (order.received_by?.display_name || order.received_by?.name) {
                        Recibida por {{ order.received_by?.display_name || order.received_by?.name }}
                      } @else if (order.created_by?.display_name || order.created_by?.name) {
                        Creada por {{ order.created_by?.display_name || order.created_by?.name }}
                      } @else {
                        Sin usuario asociado
                      }
                    </span>
                  </div>
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
    .purchase-form-items {
      display: grid;
      gap: 0.85rem;
    }

    .purchase-order-row,
    .purchase-detail-item,
    .purchase-form-item {
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
    .purchase-detail-item {
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

    @media (max-width: 1180px) {
      .purchases-layout,
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
  private readonly purchasesApi = inject(PurchasesApiService);
  private readonly suppliersApi = inject(SuppliersApiService);
  private readonly productsApi = inject(ProductsApiService);

  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly receivingId = signal<number | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly successMessage = signal<string | null>(null);

  protected readonly orders = signal<PurchaseOrder[]>([]);
  protected readonly suppliers = signal<BusinessPartner[]>([]);
  protected readonly products = signal<Product[]>([]);
  protected readonly search = signal('');
  protected readonly statusFilter = signal('');
  protected readonly selectedOrderId = signal<number | null>(null);
  protected readonly editingOrderId = signal<number | null>(null);

  protected readonly selectedOrder = computed(() =>
    this.orders().find((order) => order.id === this.selectedOrderId()) ?? null,
  );

  protected readonly pendingOrdersCount = computed(
    () => this.orders().filter((order) => order.status === 'ordered').length,
  );

  protected readonly receivedOrdersCount = computed(
    () => this.orders().filter((order) => order.status === 'received').length,
  );

  protected readonly totalPurchased = computed(() =>
    this.orders().reduce((total, order) => total + order.grand_total, 0),
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

  public constructor() {
    void this.load();
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    try {
      await Promise.all([this.loadOrders(), this.loadSuppliers(), this.loadProducts()]);
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
      return;
    }

    const currentSelectedId = this.selectedOrderId();
    const selected = currentSelectedId ? orders.find((order) => order.id === currentSelectedId) : null;
    this.selectedOrderId.set(selected?.id ?? orders[0]?.id ?? null);
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
  }

  protected selectOnly(order: PurchaseOrder, event: Event): void {
    event.stopPropagation();
    this.selectOrder(order);
  }

  protected editOrder(order: PurchaseOrder, event: Event): void {
    event.stopPropagation();
    this.selectedOrderId.set(order.id);
    this.editingOrderId.set(order.id);
    this.applyOrderToForm(order);
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

  protected resetForm(resetSelection = true): void {
    this.editingOrderId.set(null);
    this.form.reset({
      supplier_id: '',
      reference: '',
      ordered_at: this.todayDateInput(),
      notes: '',
    });
    this.itemsArray.clear();
    this.itemsArray.push(this.createItemGroup());

    if (resetSelection) {
      this.selectedOrderId.set(this.orders()[0]?.id ?? null);
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
      default:
        return status;
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
