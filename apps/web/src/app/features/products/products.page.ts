import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AuthService } from '../../core/auth/auth.service';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { ProductsApiService } from './products.api';
import {
  Product,
  ProductCategory,
  ProductCategoryPayload,
  ProductPayload,
  ProductStockAdjustmentPayload,
} from './products.types';

@Component({
  selector: 'app-products-page',
  imports: [ReactiveFormsModule],
  template: `
    <section class="stack">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Catalogo comercial</span>
          <h1 class="page-title">Productos vendibles, categorias y stock real.</h1>
          <p class="page-description">
            Este modulo ya vive sobre la API nueva. Aqui se administra lo que se vende en caja y
            su saldo sale de movimientos trazables, no de una cifra aislada.
          </p>
        </div>
        <span class="pill">Migracion real del dominio Catalog</span>
      </header>

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Productos totales</span>
          <strong class="metric-card__value">{{ products().length }}</strong>
          <span class="metric-card__hint">Catalogo comercial disponible para venta.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Con stock trazado</span>
          <strong class="metric-card__value">{{ trackedProductsCount() }}</strong>
          <span class="metric-card__hint">Productos que se controlan por movimientos.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Stock bajo</span>
          <strong class="metric-card__value">{{ lowStockCount() }}</strong>
          <span class="metric-card__hint">Items que ya tocaron o bajaron del minimo.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Categorias activas</span>
          <strong class="metric-card__value">{{ activeCategoriesCount() }}</strong>
          <span class="metric-card__hint">Clasificaciones comerciales disponibles.</span>
        </article>
      </section>

      <section class="admin-layout">
        <article class="surface stack">
          <div class="toolbar">
            <div class="field field--search">
              <label for="product-search">Buscar productos</label>
              <input
                id="product-search"
                type="text"
                [value]="search()"
                (input)="onSearch($event)"
                placeholder="Nombre, SKU o codigo de barras"
              />
            </div>

            <div class="cta-row">
              <button class="btn" type="button" (click)="loadProducts()">Refrescar</button>
              @if (isAdmin()) {
                <button class="btn btn--ghost" type="button" (click)="resetProductForm()">
                  Nuevo
                </button>
              }
            </div>
          </div>

          @if (error()) {
            <p class="alert alert--danger">{{ error() }}</p>
          }

          @if (products().length === 0) {
            <p class="muted">
              Todavia no hay productos en el catalogo nuevo. Crea el primero desde el panel de la
              derecha.
            </p>
          } @else {
            <div class="table-list">
              @for (product of products(); track product.id) {
                <article class="table-list__row">
                  <div class="table-list__copy">
                    <strong>{{ product.name }}</strong>
                    <div class="badge-row">
                      <span class="pill">{{ product.category?.name || 'Sin categoria' }}</span>
                      <span class="pill pill--muted">
                        Stock {{ formatNumber(product.current_stock) }} {{ product.unit }}
                      </span>
                      @if (product.track_stock && product.current_stock <= product.minimum_stock) {
                        <span class="pill pill--danger">Stock bajo</span>
                      }
                      @if (!product.is_active) {
                        <span class="pill pill--warning">Inactivo</span>
                      }
                    </div>
                    <p>
                      {{ product.sku || 'Sin SKU' }} · Venta {{ formatCurrency(product.sale_price) }}
                      · Costo {{ formatCurrency(product.cost_price) }}
                    </p>
                    <small>
                      Minimo {{ formatNumber(product.minimum_stock) }} ·
                      {{ product.track_stock ? 'Con control de stock' : 'Sin control de stock' }}
                    </small>
                  </div>

                  <div class="table-list__actions">
                    <button class="btn btn--ghost" type="button" (click)="editProduct(product)">
                      Editar
                    </button>
                    @if (isAdmin()) {
                      <button class="btn" type="button" (click)="deleteProduct(product)">
                        Eliminar
                      </button>
                    }
                  </div>
                </article>
              }
            </div>
          }
        </article>

        <div class="stack">
          @if (isAdmin()) {
            <article class="surface stack">
              <div class="page-header">
                <div class="page-header__copy">
                  <span class="page-kicker">Formulario</span>
                  <h2 class="page-title">
                    @if (editingProductId()) {
                      Editar producto
                    } @else {
                      Crear producto
                    }
                  </h2>
                  <p class="page-description">
                    Productos y activos ya no se mezclan. Este formulario solo gobierna el catalogo
                    comercial y su logica de venta.
                  </p>
                </div>
              </div>

              <form class="form-grid" [formGroup]="productForm" (ngSubmit)="submitProduct()">
                <div class="split">
                  <div class="field">
                    <label for="product-name">Nombre</label>
                    <input id="product-name" type="text" formControlName="name" />
                  </div>

                  <div class="field">
                    <label for="product-category">Categoria</label>
                    <select id="product-category" formControlName="category_id">
                      <option value="">Sin categoria</option>
                      @for (category of categories(); track category.id) {
                        <option [value]="category.id">{{ category.name }}</option>
                      }
                    </select>
                  </div>
                </div>

                <div class="split">
                  <div class="field">
                    <label for="product-sku">SKU</label>
                    <input id="product-sku" type="text" formControlName="sku" />
                  </div>

                  <div class="field">
                    <label for="product-barcode">Codigo de barras</label>
                    <input id="product-barcode" type="text" formControlName="barcode" />
                  </div>
                </div>

                <div class="split">
                  <div class="field">
                    <label for="product-sale-price">Precio de venta</label>
                    <input id="product-sale-price" type="number" min="0" step="0.01" formControlName="sale_price" />
                  </div>

                  <div class="field">
                    <label for="product-cost-price">Costo</label>
                    <input id="product-cost-price" type="number" min="0" step="0.01" formControlName="cost_price" />
                  </div>
                </div>

                <div class="split">
                  <div class="field">
                    <label for="product-tax-rate">Impuesto</label>
                    <input id="product-tax-rate" type="number" min="0" step="0.01" formControlName="tax_rate" />
                  </div>

                  <div class="field">
                    <label for="product-unit">Unidad</label>
                    <input id="product-unit" type="text" formControlName="unit" />
                  </div>
                </div>

                <div class="split">
                  <div class="field">
                    <label for="product-track-stock">Control de stock</label>
                    <select id="product-track-stock" formControlName="track_stock">
                      <option [ngValue]="true">Si</option>
                      <option [ngValue]="false">No</option>
                    </select>
                  </div>

                  <div class="field">
                    <label for="product-minimum-stock">Stock minimo</label>
                    <input
                      id="product-minimum-stock"
                      type="number"
                      min="0"
                      step="0.01"
                      formControlName="minimum_stock"
                    />
                  </div>
                </div>

                @if (showInitialStockField()) {
                  <div class="field">
                    <label for="product-initial-stock">Stock inicial</label>
                    <input
                      id="product-initial-stock"
                      type="number"
                      step="0.01"
                      formControlName="initial_stock"
                    />
                  </div>
                }

                <div class="field">
                  <label for="product-description">Descripcion</label>
                  <textarea id="product-description" formControlName="description"></textarea>
                </div>

                <div class="field">
                  <label for="product-is-active">Estado</label>
                  <select id="product-is-active" formControlName="is_active">
                    <option [ngValue]="true">Activo</option>
                    <option [ngValue]="false">Inactivo</option>
                  </select>
                </div>

                @if (error()) {
                  <p class="alert alert--danger">{{ error() }}</p>
                }

                <div class="cta-row">
                  <button class="btn btn--primary" type="submit" [disabled]="savingProduct()">
                    @if (savingProduct()) {
                      Guardando...
                    } @else if (editingProductId()) {
                      Actualizar producto
                    } @else {
                      Crear producto
                    }
                  </button>
                  <button class="btn btn--ghost" type="button" (click)="resetProductForm()">
                    Limpiar
                  </button>
                </div>
              </form>
            </article>

            @if (editingProductId() && canAdjustStock()) {
              <article class="surface stack">
                <div class="page-header">
                  <div class="page-header__copy">
                    <span class="page-kicker">Ajuste operativo</span>
                    <h2 class="page-title">Corregir stock</h2>
                    <p class="page-description">
                      Este ajuste crea un movimiento real. Usa positivo para entrada y negativo para
                      salida o correccion.
                    </p>
                  </div>
                </div>

                <form class="form-grid" [formGroup]="adjustmentForm" (ngSubmit)="submitAdjustment()">
                  <div class="field">
                    <label for="product-adjustment-quantity">Cantidad</label>
                    <input
                      id="product-adjustment-quantity"
                      type="number"
                      step="0.01"
                      formControlName="quantity"
                    />
                  </div>

                  <div class="field">
                    <label for="product-adjustment-reason">Motivo</label>
                    <input
                      id="product-adjustment-reason"
                      type="text"
                      formControlName="reason"
                      placeholder="conteo, merma, ajuste manual"
                    />
                  </div>

                  <div class="field">
                    <label for="product-adjustment-notes">Notas</label>
                    <textarea id="product-adjustment-notes" formControlName="notes"></textarea>
                  </div>

                  @if (adjustmentError()) {
                    <p class="alert alert--danger">{{ adjustmentError() }}</p>
                  }

                  <div class="cta-row">
                    <button class="btn btn--primary" type="submit" [disabled]="savingAdjustment()">
                      @if (savingAdjustment()) {
                        Aplicando...
                      } @else {
                        Registrar ajuste
                      }
                    </button>
                  </div>
                </form>
              </article>
            }

            <article class="surface stack">
              <div class="page-header">
                <div class="page-header__copy">
                  <span class="page-kicker">Categorias</span>
                  <h2 class="page-title">Mantener clasificacion comercial</h2>
                  <p class="page-description">
                    Las categorias viven aparte del formulario principal para que el catalogo siga
                    ordenado y reusable.
                  </p>
                </div>
              </div>

              <form class="form-grid" [formGroup]="categoryForm" (ngSubmit)="submitCategory()">
                <div class="field">
                  <label for="product-category-name">Nombre</label>
                  <input id="product-category-name" type="text" formControlName="name" />
                </div>

                <div class="field">
                  <label for="product-category-description">Descripcion</label>
                  <textarea id="product-category-description" formControlName="description"></textarea>
                </div>

                <div class="field">
                  <label for="product-category-active">Estado</label>
                  <select id="product-category-active" formControlName="is_active">
                    <option [ngValue]="true">Activa</option>
                    <option [ngValue]="false">Inactiva</option>
                  </select>
                </div>

                @if (categoryError()) {
                  <p class="alert alert--danger">{{ categoryError() }}</p>
                }

                <div class="cta-row">
                  <button class="btn btn--primary" type="submit" [disabled]="savingCategory()">
                    @if (savingCategory()) {
                      Guardando...
                    } @else if (editingCategoryId()) {
                      Actualizar categoria
                    } @else {
                      Crear categoria
                    }
                  </button>
                  <button class="btn btn--ghost" type="button" (click)="resetCategoryForm()">
                    Limpiar
                  </button>
                </div>
              </form>

              <div class="table-list">
                @for (category of categories(); track category.id) {
                  <article class="table-list__row">
                    <div class="table-list__copy">
                      <strong>{{ category.name }}</strong>
                      <p>{{ category.description || 'Sin descripcion' }}</p>
                      <small>Slug: {{ category.slug }}</small>
                    </div>

                    <div class="table-list__actions">
                      <span class="pill" [class.pill--warning]="!category.is_active">
                        {{ category.is_active ? 'Activa' : 'Inactiva' }}
                      </span>
                      <button class="btn btn--ghost" type="button" (click)="editCategory(category)">
                        Editar
                      </button>
                      <button class="btn" type="button" (click)="deleteCategory(category)">
                        Eliminar
                      </button>
                    </div>
                  </article>
                }
              </div>
            </article>
          } @else {
            <article class="surface stack">
              <span class="page-kicker">Solo lectura</span>
              <h2 class="page-title">Tu rol puede consultar, no administrar.</h2>
              <p class="page-description">
                El catalogo y el stock ya se pueden revisar desde aqui, pero las altas, ajustes y
                eliminaciones quedan reservadas para administracion.
              </p>
            </article>
          }
        </div>
      </section>
    </section>
  `,
})
export class ProductsPageComponent {
  private readonly auth = inject(AuthService);
  private readonly api = inject(ProductsApiService);
  private readonly fb = inject(FormBuilder);

  protected readonly isAdmin = this.auth.isAdmin;
  protected readonly products = signal<Product[]>([]);
  protected readonly categories = signal<ProductCategory[]>([]);
  protected readonly search = signal('');
  protected readonly savingProduct = signal(false);
  protected readonly savingCategory = signal(false);
  protected readonly savingAdjustment = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly categoryError = signal<string | null>(null);
  protected readonly adjustmentError = signal<string | null>(null);
  protected readonly editingProductId = signal<number | null>(null);
  protected readonly editingCategoryId = signal<number | null>(null);

  protected readonly trackedProductsCount = computed(
    () => this.products().filter((product) => product.track_stock).length,
  );
  protected readonly lowStockCount = computed(
    () =>
      this.products().filter(
        (product) => product.track_stock && product.current_stock <= product.minimum_stock,
      ).length,
  );
  protected readonly activeCategoriesCount = computed(
    () => this.categories().filter((category) => category.is_active).length,
  );

  protected readonly productForm = this.fb.group({
    name: ['', [Validators.required]],
    category_id: [''],
    sku: [''],
    barcode: [''],
    sale_price: [0, [Validators.required, Validators.min(0)]],
    cost_price: [0, [Validators.required, Validators.min(0)]],
    tax_rate: [0, [Validators.min(0)]],
    unit: ['unidad', [Validators.required]],
    track_stock: [true, [Validators.required]],
    minimum_stock: [0, [Validators.min(0)]],
    initial_stock: [0],
    description: [''],
    is_active: [true, [Validators.required]],
  });

  protected readonly categoryForm = this.fb.group({
    name: ['', [Validators.required]],
    description: [''],
    is_active: [true, [Validators.required]],
  });

  protected readonly adjustmentForm = this.fb.group({
    quantity: [0, [Validators.required]],
    reason: [''],
    notes: [''],
  });

  private readonly moneyFormatter = new Intl.NumberFormat('es-EC', {
    style: 'currency',
    currency: 'USD',
  });

  private readonly numberFormatter = new Intl.NumberFormat('es-EC', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });

  public constructor() {
    void this.load();
  }

  protected async load(): Promise<void> {
    await Promise.all([this.loadProducts(), this.loadCategories()]);
  }

  protected async loadProducts(): Promise<void> {
    try {
      this.error.set(null);
      this.products.set(await this.api.listProducts(this.search()));
    } catch (error) {
      this.error.set(resolveApiError(error));
    }
  }

  protected async loadCategories(): Promise<void> {
    try {
      this.categoryError.set(null);
      this.categories.set(await this.api.listCategories());
    } catch (error) {
      this.categoryError.set(resolveApiError(error));
    }
  }

  protected onSearch(event: Event): void {
    const target = event.target as HTMLInputElement;
    this.search.set(target.value);
    void this.loadProducts();
  }

  protected editProduct(product: Product): void {
    this.editingProductId.set(product.id);
    this.error.set(null);
    this.adjustmentError.set(null);
    this.productForm.patchValue({
      name: product.name,
      category_id: String(product.category?.id ?? ''),
      sku: product.sku ?? '',
      barcode: product.barcode ?? '',
      sale_price: product.sale_price,
      cost_price: product.cost_price,
      tax_rate: product.tax_rate,
      unit: product.unit,
      track_stock: product.track_stock,
      minimum_stock: product.minimum_stock,
      initial_stock: 0,
      description: product.description ?? '',
      is_active: product.is_active,
    });
    this.adjustmentForm.reset({
      quantity: 0,
      reason: '',
      notes: '',
    });
  }

  protected resetProductForm(): void {
    this.editingProductId.set(null);
    this.error.set(null);
    this.adjustmentError.set(null);
    this.productForm.reset({
      name: '',
      category_id: '',
      sku: '',
      barcode: '',
      sale_price: 0,
      cost_price: 0,
      tax_rate: 0,
      unit: 'unidad',
      track_stock: true,
      minimum_stock: 0,
      initial_stock: 0,
      description: '',
      is_active: true,
    });
    this.adjustmentForm.reset({
      quantity: 0,
      reason: '',
      notes: '',
    });
  }

  protected editCategory(category: ProductCategory): void {
    this.editingCategoryId.set(category.id);
    this.categoryError.set(null);
    this.categoryForm.patchValue({
      name: category.name,
      description: category.description ?? '',
      is_active: category.is_active,
    });
  }

  protected resetCategoryForm(): void {
    this.editingCategoryId.set(null);
    this.categoryError.set(null);
    this.categoryForm.reset({
      name: '',
      description: '',
      is_active: true,
    });
  }

  protected showInitialStockField(): boolean {
    return !this.editingProductId() && Boolean(this.productForm.getRawValue().track_stock);
  }

  protected canAdjustStock(): boolean {
    return Boolean(this.productForm.getRawValue().track_stock);
  }

  protected async submitProduct(): Promise<void> {
    if (this.productForm.invalid) {
      this.productForm.markAllAsTouched();
      return;
    }

    this.savingProduct.set(true);
    this.error.set(null);

    try {
      const payload = this.buildProductPayload();

      if (this.editingProductId()) {
        await this.api.updateProduct(this.editingProductId() as number, payload);
      } else {
        await this.api.createProduct(payload);
      }

      this.resetProductForm();
      await this.loadProducts();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.savingProduct.set(false);
    }
  }

  protected async submitCategory(): Promise<void> {
    if (this.categoryForm.invalid) {
      this.categoryForm.markAllAsTouched();
      return;
    }

    this.savingCategory.set(true);
    this.categoryError.set(null);

    try {
      const payload = this.buildCategoryPayload();

      if (this.editingCategoryId()) {
        await this.api.updateCategory(this.editingCategoryId() as number, payload);
      } else {
        await this.api.createCategory(payload);
      }

      this.resetCategoryForm();
      await Promise.all([this.loadCategories(), this.loadProducts()]);
    } catch (error) {
      this.categoryError.set(resolveApiError(error));
    } finally {
      this.savingCategory.set(false);
    }
  }

  protected async submitAdjustment(): Promise<void> {
    if (this.adjustmentForm.invalid || !this.editingProductId()) {
      this.adjustmentForm.markAllAsTouched();
      return;
    }

    this.savingAdjustment.set(true);
    this.adjustmentError.set(null);

    try {
      const payload = this.buildAdjustmentPayload();

      await this.api.adjustStock(this.editingProductId() as number, payload);

      this.adjustmentForm.reset({
        quantity: 0,
        reason: '',
        notes: '',
      });

      await this.loadProducts();
    } catch (error) {
      this.adjustmentError.set(resolveApiError(error));
    } finally {
      this.savingAdjustment.set(false);
    }
  }

  protected async deleteProduct(product: Product): Promise<void> {
    if (!window.confirm(`Estas seguro de eliminar el producto ${product.name}?`)) {
      return;
    }

    try {
      await this.api.deleteProduct(product.id);

      if (this.editingProductId() === product.id) {
        this.resetProductForm();
      }

      await this.loadProducts();
    } catch (error) {
      this.error.set(resolveApiError(error));
    }
  }

  protected async deleteCategory(category: ProductCategory): Promise<void> {
    if (!window.confirm(`Estas seguro de eliminar la categoria ${category.name}?`)) {
      return;
    }

    try {
      await this.api.deleteCategory(category.id);

      if (this.editingCategoryId() === category.id) {
        this.resetCategoryForm();
      }

      await Promise.all([this.loadCategories(), this.loadProducts()]);
    } catch (error) {
      this.categoryError.set(resolveApiError(error));
    }
  }

  protected formatCurrency(value: number): string {
    return this.moneyFormatter.format(value);
  }

  protected formatNumber(value: number): string {
    return this.numberFormatter.format(value);
  }

  private buildProductPayload(): ProductPayload {
    const raw = this.productForm.getRawValue();

    const payload: ProductPayload = {
      name: raw.name?.trim() ?? '',
      description: this.nullableText(raw.description),
      sku: this.nullableText(raw.sku),
      barcode: this.nullableText(raw.barcode),
      category_id: raw.category_id ? Number(raw.category_id) : null,
      sale_price: Number(raw.sale_price ?? 0),
      cost_price: Number(raw.cost_price ?? 0),
      tax_rate: Number(raw.tax_rate ?? 0),
      unit: raw.unit?.trim() || 'unidad',
      track_stock: Boolean(raw.track_stock),
      minimum_stock: Number(raw.minimum_stock ?? 0),
      is_active: Boolean(raw.is_active),
    };

    if (!this.editingProductId() && payload.track_stock) {
      payload.initial_stock = Number(raw.initial_stock ?? 0);
    }

    return payload;
  }

  private buildCategoryPayload(): ProductCategoryPayload {
    const raw = this.categoryForm.getRawValue();

    return {
      name: raw.name?.trim() ?? '',
      description: this.nullableText(raw.description),
      is_active: Boolean(raw.is_active),
    };
  }

  private buildAdjustmentPayload(): ProductStockAdjustmentPayload {
    const raw = this.adjustmentForm.getRawValue();

    return {
      quantity: Number(raw.quantity ?? 0),
      reason: this.nullableText(raw.reason),
      notes: this.nullableText(raw.notes),
    };
  }

  private nullableText(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }
}
