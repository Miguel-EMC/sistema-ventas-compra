import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatDividerModule } from '@angular/material/divider';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatListModule } from '@angular/material/list';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSelectModule } from '@angular/material/select';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { CustomersApiService } from '../customers/customers.api';
import { BusinessPartner } from '../partners/partners.types';
import { ProductsApiService } from '../products/products.api';
import { Product } from '../products/products.types';
import { SalesApiService } from './sales.api';
import {
  CheckoutSalePayload,
  SaleDraft,
  SaleDraftItem,
  SaleRecord,
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
    MatIconModule,
    MatInputModule,
    MatListModule,
    MatProgressBarModule,
    MatSelectModule,
  ],
  template: `
    <section class="stack sales-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">POS y checkout</span>
          <h1 class="page-title">Borrador real, checkout y ventas sobre la API nueva.</h1>
          <p class="page-description">
            Este flujo ya reemplaza la preventa global legacy por un borrador aislado por usuario,
            con detalle, pago y rebaja de stock trazable.
          </p>
        </div>
        <span class="pill">Angular Material + dominio Sales</span>
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
          <span class="page-kicker">Ultima venta</span>
          <strong>{{ successMessage() }}</strong>
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
      </section>

      <section class="sales-layout">
        <mat-card appearance="outlined" class="sales-card">
          <mat-card-header>
            <mat-card-title>Cabecera del borrador</mat-card-title>
            <mat-card-subtitle>Cliente, notas y seleccion de producto.</mat-card-subtitle>
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
                  [disabled]="addingItem() || products().length === 0"
                >
                  Agregar al borrador
                </button>
              </div>
            </form>
          </mat-card-content>
        </mat-card>

        <mat-card appearance="outlined" class="sales-card sales-card--draft">
          <mat-card-header>
            <mat-card-title>Borrador activo</mat-card-title>
            <mat-card-subtitle>
              {{ draft()?.customer?.name || 'Consumidor final' }}
              · {{ draft()?.channel || 'pos' }}
            </mat-card-subtitle>
          </mat-card-header>

          <mat-card-content class="stack">
            <mat-chip-set>
              <mat-chip>Subtotal {{ formatCurrency(draft()?.subtotal ?? 0) }}</mat-chip>
              <mat-chip>Impuesto {{ formatCurrency(draft()?.tax_total ?? 0) }}</mat-chip>
              <mat-chip highlighted>Total {{ formatCurrency(draft()?.grand_total ?? 0) }}</mat-chip>
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
                      <button mat-icon-button type="button" (click)="decreaseQuantity(item)">
                        <mat-icon>remove</mat-icon>
                      </button>
                      <button mat-icon-button type="button" (click)="increaseQuantity(item)">
                        <mat-icon>add</mat-icon>
                      </button>
                      <button mat-icon-button type="button" (click)="removeItem(item)">
                        <mat-icon>delete</mat-icon>
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
                <mat-select formControlName="document_type">
                  @for (option of documentTypes; track option.value) {
                    <mat-option [value]="option.value">{{ option.label }}</mat-option>
                  }
                </mat-select>
              </mat-form-field>

              <mat-form-field appearance="outline">
                <mat-label>Metodo de pago</mat-label>
                <mat-select formControlName="payment_method">
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

              <div class="cta-row">
                <button
                  mat-flat-button
                  color="primary"
                  type="submit"
                  [disabled]="checkingOut() || (draft()?.items?.length ?? 0) === 0"
                >
                  Confirmar venta
                </button>
              </div>
            </form>
          </mat-card-content>
        </mat-card>

        <mat-card appearance="outlined" class="sales-card">
          <mat-card-header>
            <mat-card-title>Ventas recientes</mat-card-title>
            <mat-card-subtitle>Ultimas transacciones confirmadas por la API.</mat-card-subtitle>
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
                      <small>{{ formatDateTime(sale.sold_at) }}</small>
                    </div>
                    <div class="sales-history-item__amount">
                      {{ formatCurrency(sale.grand_total) }}
                    </div>
                  </mat-list-item>
                  <mat-divider></mat-divider>
                }
              </mat-list>
            }
          </mat-card-content>
        </mat-card>
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

    .draft-items {
      display: grid;
      gap: 0.75rem;
    }

    .draft-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: rgba(15, 76, 129, 0.03);
      padding: 1rem;
    }

    .draft-item__copy {
      display: grid;
      gap: 0.45rem;
      min-width: 0;
    }

    .draft-item__copy small {
      color: var(--text-muted);
    }

    .draft-item__actions {
      display: flex;
      align-items: center;
      gap: 0.25rem;
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

    .sales-history-item__content span,
    .sales-history-item__content small {
      color: var(--text-muted);
    }

    .sales-history-item__amount {
      font-weight: 700;
      white-space: nowrap;
    }

    .sales-success {
      border-color: rgba(19, 128, 77, 0.18);
      background: rgba(19, 128, 77, 0.08);
    }

    .sales-chip-warning {
      background: rgba(180, 83, 9, 0.16);
      color: #92400e;
    }

    @media (max-width: 1200px) {
      .sales-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 860px) {
      .sales-layout,
      .sales-form--checkout {
        grid-template-columns: 1fr;
      }

      .draft-item {
        align-items: start;
        flex-direction: column;
      }

      .draft-item__actions {
        width: 100%;
        justify-content: flex-end;
      }
    }
  `,
})
export class SalesPageComponent {
  private readonly fb = inject(FormBuilder);
  private readonly salesApi = inject(SalesApiService);
  private readonly productsApi = inject(ProductsApiService);
  private readonly customersApi = inject(CustomersApiService);

  protected readonly loading = signal(false);
  protected readonly addingItem = signal(false);
  protected readonly checkingOut = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly successMessage = signal<string | null>(null);

  protected readonly draft = signal<SaleDraft | null>(null);
  protected readonly products = signal<Product[]>([]);
  protected readonly customers = signal<BusinessPartner[]>([]);
  protected readonly recentSales = signal<SaleRecord[]>([]);
  protected readonly selectedProductId = signal<number | null>(null);

  protected readonly selectedProduct = computed(() => {
    const productId = this.selectedProductId();

    if (productId === null) {
      return null;
    }

    return this.products().find((product) => product.id === productId) ?? null;
  });

  protected readonly paymentMethods = [
    { value: 'cash', label: 'Efectivo' },
    { value: 'card', label: 'Tarjeta' },
    { value: 'transfer', label: 'Transferencia' },
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
      await Promise.all([
        this.loadDraft(),
        this.loadProducts(),
        this.loadCustomers(),
        this.loadRecentSales(),
      ]);
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

    this.checkingOut.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const syncedDraft = await this.salesApi.updateDraft(this.buildDraftPayload());
      this.draft.set(syncedDraft);

      const sale = await this.salesApi.checkout(this.buildCheckoutPayload());
      this.successMessage.set(
        `Venta ${sale.public_id || '#' + sale.id} confirmada por ${this.formatCurrency(sale.grand_total)}.`,
      );

      await Promise.all([this.loadDraft(), this.loadRecentSales(), this.loadProducts()]);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.checkingOut.set(false);
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

  private nullableText(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }

  private syncCheckoutAmount(draft: SaleDraft): void {
    this.checkoutForm.patchValue({
      amount_paid: draft.grand_total,
    });
  }

  protected onProductSelectionChange(value: string | number | null): void {
    this.selectedProductId.set(value ? Number(value) : null);
  }
}
