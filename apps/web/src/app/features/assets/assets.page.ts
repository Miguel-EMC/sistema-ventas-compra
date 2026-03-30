import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { AssetsApiService } from './assets.api';
import { Asset, AssetCategory, AssetCategoryPayload, AssetPayload } from './assets.types';

@Component({
  selector: 'app-assets-page',
  imports: [ReactiveFormsModule],
  template: `
    <section class="stack">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Activos internos</span>
          <h1 class="page-title">Equipos, insumos y recursos operativos.</h1>
          <p class="page-description">
            Este modulo reemplaza la confusion del legacy. Aqui viven los activos del negocio que
            no se venden ni participan en el checkout del POS.
          </p>
        </div>
        <span class="pill">Migracion real del dominio Assets</span>
      </header>

      @if (legacyNotice()) {
        <article class="surface surface--muted stack">
          <span class="page-kicker">Migracion</span>
          <strong>{{ legacyNotice() }}</strong>
        </article>
      }

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Activos registrados</span>
          <strong class="metric-card__value">{{ assets().length }}</strong>
          <span class="metric-card__hint">Equipos e insumos internos del negocio.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Unidades totales</span>
          <strong class="metric-card__value">{{ formatNumber(totalUnits()) }}</strong>
          <span class="metric-card__hint">Cantidad agregada de activos en operacion.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">En servicio</span>
          <strong class="metric-card__value">{{ activeAssetsCount() }}</strong>
          <span class="metric-card__hint">Activos con estado operativo activo.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Categorias</span>
          <strong class="metric-card__value">{{ categories().length }}</strong>
          <span class="metric-card__hint">Clasificaciones internas reutilizables.</span>
        </article>
      </section>

      <section class="admin-layout">
        <article class="surface stack">
          <div class="toolbar">
            <div class="field field--search">
              <label for="asset-search">Buscar activos</label>
              <input
                id="asset-search"
                type="text"
                [value]="search()"
                (input)="onSearch($event)"
                placeholder="Nombre, codigo o estado"
              />
            </div>

            <div class="cta-row">
              <button
                class="btn btn--ghost"
                type="button"
                (click)="downloadCatalogPdf()"
                [disabled]="downloadingPdf()"
              >
                {{ downloadingPdf() ? 'Descargando PDF...' : 'PDF inventario' }}
              </button>
              <button
                class="btn btn--ghost"
                type="button"
                (click)="downloadCatalogCsv()"
                [disabled]="downloadingCsv()"
              >
                {{ downloadingCsv() ? 'Descargando CSV...' : 'CSV inventario' }}
              </button>
              <button class="btn" type="button" (click)="loadAssets()">Refrescar</button>
              @if (isAdmin()) {
                <button class="btn btn--ghost" type="button" (click)="resetAssetForm()">
                  Nuevo
                </button>
              }
            </div>
          </div>

          @if (error()) {
            <p class="alert alert--danger">{{ error() }}</p>
          }

          @if (assets().length === 0) {
            <p class="muted">
              Todavia no hay activos cargados en el dominio nuevo. Registra el primero desde el
              panel lateral.
            </p>
          } @else {
            <div class="table-list">
              @for (asset of assets(); track asset.id) {
                <article class="table-list__row">
                  <div class="table-list__copy">
                    <strong>{{ asset.name }}</strong>
                    <div class="badge-row">
                      <span class="pill">{{ asset.category?.name || 'Sin categoria' }}</span>
                      <span class="pill pill--muted">Cantidad {{ formatNumber(asset.quantity) }}</span>
                      <span class="pill" [class.pill--warning]="asset.status !== 'active'">
                        {{ labelForStatus(asset.status) }}
                      </span>
                    </div>
                    <p>
                      {{ asset.code || 'Sin codigo' }}
                      · {{ asset.acquisition_cost !== null ? formatCurrency(asset.acquisition_cost) : 'Sin costo' }}
                    </p>
                    <small>
                      {{ asset.acquired_at ? ('Adquirido el ' + asset.acquired_at) : 'Sin fecha de adquisicion' }}
                    </small>
                  </div>

                  <div class="table-list__actions">
                    <button class="btn btn--ghost" type="button" (click)="editAsset(asset)">
                      Editar
                    </button>
                    @if (isAdmin()) {
                      <button class="btn" type="button" (click)="deleteAsset(asset)">Eliminar</button>
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
                    @if (editingAssetId()) {
                      Editar activo
                    } @else {
                      Registrar activo
                    }
                  </h2>
                  <p class="page-description">
                    Aqui se registran bienes internos del negocio. Si algo se vende en caja, no va
                    en este modulo: va en Productos.
                  </p>
                </div>
              </div>

              <form class="form-grid" [formGroup]="assetForm" (ngSubmit)="submitAsset()">
                <div class="split">
                  <div class="field">
                    <label for="asset-name">Nombre</label>
                    <input id="asset-name" type="text" formControlName="name" />
                  </div>

                  <div class="field">
                    <label for="asset-category">Categoria</label>
                    <select id="asset-category" formControlName="category_id">
                      <option value="">Sin categoria</option>
                      @for (category of categories(); track category.id) {
                        <option [value]="category.id">{{ category.name }}</option>
                      }
                    </select>
                  </div>
                </div>

                <div class="split">
                  <div class="field">
                    <label for="asset-code">Codigo</label>
                    <input id="asset-code" type="text" formControlName="code" />
                  </div>

                  <div class="field">
                    <label for="asset-status">Estado</label>
                    <select id="asset-status" formControlName="status">
                      @for (status of assetStatuses; track status.value) {
                        <option [value]="status.value">{{ status.label }}</option>
                      }
                    </select>
                  </div>
                </div>

                <div class="split">
                  <div class="field">
                    <label for="asset-quantity">Cantidad</label>
                    <input id="asset-quantity" type="number" min="0" step="0.01" formControlName="quantity" />
                  </div>

                  <div class="field">
                    <label for="asset-acquisition-cost">Costo de adquisicion</label>
                    <input
                      id="asset-acquisition-cost"
                      type="number"
                      min="0"
                      step="0.01"
                      formControlName="acquisition_cost"
                    />
                  </div>
                </div>

                <div class="field">
                  <label for="asset-acquired-at">Fecha de adquisicion</label>
                  <input id="asset-acquired-at" type="date" formControlName="acquired_at" />
                </div>

                <div class="field">
                  <label for="asset-description">Descripcion</label>
                  <textarea id="asset-description" formControlName="description"></textarea>
                </div>

                @if (error()) {
                  <p class="alert alert--danger">{{ error() }}</p>
                }

                <div class="cta-row">
                  <button class="btn btn--primary" type="submit" [disabled]="savingAsset()">
                    @if (savingAsset()) {
                      Guardando...
                    } @else if (editingAssetId()) {
                      Actualizar activo
                    } @else {
                      Crear activo
                    }
                  </button>
                  <button class="btn btn--ghost" type="button" (click)="resetAssetForm()">
                    Limpiar
                  </button>
                </div>
              </form>
            </article>

            <article class="surface stack">
              <div class="page-header">
                <div class="page-header__copy">
                  <span class="page-kicker">Categorias</span>
                  <h2 class="page-title">Organizar activos internos</h2>
                  <p class="page-description">
                    Usa categorias como tecnologia, mobiliario o insumos para no mezclar activos
                    con el catalogo de venta.
                  </p>
                </div>
              </div>

              <form class="form-grid" [formGroup]="categoryForm" (ngSubmit)="submitCategory()">
                <div class="field">
                  <label for="asset-category-name">Nombre</label>
                  <input id="asset-category-name" type="text" formControlName="name" />
                </div>

                <div class="field">
                  <label for="asset-category-description">Descripcion</label>
                  <textarea id="asset-category-description" formControlName="description"></textarea>
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
              <h2 class="page-title">Tu rol puede consultar activos.</h2>
              <p class="page-description">
                Las altas y cambios de activos internos quedan reservados a administracion, pero la
                consulta ya esta disponible desde el frontend nuevo.
              </p>
            </article>
          }
        </div>
      </section>
    </section>
  `,
})
export class AssetsPageComponent {
  private readonly auth = inject(AuthService);
  private readonly api = inject(AssetsApiService);
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private legacyPrefillApplied = false;
  private legacyAutoExportHandled = false;

  protected readonly isAdmin = this.auth.isAdmin;
  protected readonly assets = signal<Asset[]>([]);
  protected readonly categories = signal<AssetCategory[]>([]);
  protected readonly search = signal('');
  protected readonly downloadingPdf = signal(false);
  protected readonly downloadingCsv = signal(false);
  protected readonly savingAsset = signal(false);
  protected readonly savingCategory = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly categoryError = signal<string | null>(null);
  protected readonly editingAssetId = signal<number | null>(null);
  protected readonly editingCategoryId = signal<number | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);

  protected readonly totalUnits = computed(() =>
    this.assets().reduce((sum, asset) => sum + asset.quantity, 0),
  );
  protected readonly activeAssetsCount = computed(
    () => this.assets().filter((asset) => asset.status === 'active').length,
  );

  protected readonly assetStatuses = [
    { value: 'active', label: 'Activo' },
    { value: 'maintenance', label: 'Mantenimiento' },
    { value: 'inactive', label: 'Inactivo' },
    { value: 'retired', label: 'Retirado' },
  ];

  protected readonly assetForm = this.fb.group({
    name: ['', [Validators.required]],
    category_id: [''],
    code: [''],
    status: ['active', [Validators.required]],
    quantity: [1, [Validators.required, Validators.min(0)]],
    acquisition_cost: [''],
    acquired_at: [''],
    description: [''],
  });

  protected readonly categoryForm = this.fb.group({
    name: ['', [Validators.required]],
    description: [''],
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
    this.legacyNotice.set(this.resolveLegacyNotice(this.route.snapshot.queryParamMap.get('legacy')));
    void this.load();
  }

  protected async load(): Promise<void> {
    await Promise.all([this.loadAssets(), this.loadCategories()]);
    this.applyLegacyPrefillFromQuery();
    await this.runLegacyAutoExport();
  }

  protected async loadAssets(): Promise<void> {
    try {
      this.error.set(null);
      this.assets.set(await this.api.listAssets(this.search()));
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
    void this.loadAssets();
  }

  protected editAsset(asset: Asset): void {
    this.editingAssetId.set(asset.id);
    this.error.set(null);
    this.assetForm.patchValue({
      name: asset.name,
      category_id: String(asset.category?.id ?? ''),
      code: asset.code ?? '',
      status: asset.status,
      quantity: asset.quantity,
      acquisition_cost: asset.acquisition_cost?.toString() ?? '',
      acquired_at: asset.acquired_at ?? '',
      description: asset.description ?? '',
    });
  }

  protected resetAssetForm(): void {
    this.editingAssetId.set(null);
    this.error.set(null);
    this.assetForm.reset({
      name: '',
      category_id: '',
      code: '',
      status: 'active',
      quantity: 1,
      acquisition_cost: '',
      acquired_at: '',
      description: '',
    });
  }

  protected editCategory(category: AssetCategory): void {
    this.editingCategoryId.set(category.id);
    this.categoryError.set(null);
    this.categoryForm.patchValue({
      name: category.name,
      description: category.description ?? '',
    });
  }

  protected resetCategoryForm(): void {
    this.editingCategoryId.set(null);
    this.categoryError.set(null);
    this.categoryForm.reset({
      name: '',
      description: '',
    });
  }

  protected async submitAsset(): Promise<void> {
    if (this.assetForm.invalid) {
      this.assetForm.markAllAsTouched();
      return;
    }

    this.savingAsset.set(true);
    this.error.set(null);

    try {
      const payload = this.buildAssetPayload();

      if (this.editingAssetId()) {
        await this.api.updateAsset(this.editingAssetId() as number, payload);
      } else {
        await this.api.createAsset(payload);
      }

      this.resetAssetForm();
      await this.loadAssets();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.savingAsset.set(false);
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
      await Promise.all([this.loadCategories(), this.loadAssets()]);
    } catch (error) {
      this.categoryError.set(resolveApiError(error));
    } finally {
      this.savingCategory.set(false);
    }
  }

  protected async deleteAsset(asset: Asset): Promise<void> {
    if (!window.confirm(`Estas seguro de eliminar el activo ${asset.name}?`)) {
      return;
    }

    try {
      await this.api.deleteAsset(asset.id);

      if (this.editingAssetId() === asset.id) {
        this.resetAssetForm();
      }

      await this.loadAssets();
    } catch (error) {
      this.error.set(resolveApiError(error));
    }
  }

  protected async deleteCategory(category: AssetCategory): Promise<void> {
    if (!window.confirm(`Estas seguro de eliminar la categoria ${category.name}?`)) {
      return;
    }

    try {
      await this.api.deleteCategory(category.id);

      if (this.editingCategoryId() === category.id) {
        this.resetCategoryForm();
      }

      await Promise.all([this.loadCategories(), this.loadAssets()]);
    } catch (error) {
      this.categoryError.set(resolveApiError(error));
    }
  }

  protected async downloadCatalogPdf(): Promise<void> {
    this.downloadingPdf.set(true);
    this.error.set(null);

    try {
      const pdf = await this.api.downloadCatalogPdf(this.search());
      this.triggerFileDownload(pdf, this.buildCatalogFileName('catalogo-activos', 'pdf'));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.downloadingPdf.set(false);
    }
  }

  protected async downloadCatalogCsv(): Promise<void> {
    this.downloadingCsv.set(true);
    this.error.set(null);

    try {
      const csv = await this.api.downloadCatalogCsv(this.search());
      this.triggerFileDownload(csv, this.buildCatalogFileName('catalogo-activos', 'csv'));
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.downloadingCsv.set(false);
    }
  }

  protected labelForStatus(status: string): string {
    return this.assetStatuses.find((item) => item.value === status)?.label ?? status;
  }

  protected formatCurrency(value: number): string {
    return this.moneyFormatter.format(value);
  }

  protected formatNumber(value: number): string {
    return this.numberFormatter.format(value);
  }

  private buildAssetPayload(): AssetPayload {
    const raw = this.assetForm.getRawValue();

    return {
      code: this.nullableText(raw.code),
      category_id: raw.category_id ? Number(raw.category_id) : null,
      name: raw.name?.trim() ?? '',
      description: this.nullableText(raw.description),
      quantity: Number(raw.quantity ?? 0),
      acquisition_cost: raw.acquisition_cost === '' ? null : Number(raw.acquisition_cost),
      acquired_at: this.nullableText(raw.acquired_at),
      status: raw.status?.trim() || 'active',
    };
  }

  private buildCategoryPayload(): AssetCategoryPayload {
    const raw = this.categoryForm.getRawValue();

    return {
      name: raw.name?.trim() ?? '',
      description: this.nullableText(raw.description),
    };
  }

  private nullableText(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }

  private applyLegacyPrefillFromQuery(): void {
    if (this.legacyPrefillApplied || !this.isAdmin()) {
      this.legacyPrefillApplied = true;
      return;
    }

    this.legacyPrefillApplied = true;

    const query = this.route.snapshot.queryParamMap;
    const name = this.nullableText(query.get('name'));
    const code = this.nullableText(query.get('code'));
    const quantity = this.normalizeNumber(query.get('quantity'));
    const acquiredAt = this.nullableText(query.get('acquired_at'));

    if (name === null && code === null && quantity === null && acquiredAt === null) {
      return;
    }

    this.resetAssetForm();
    this.assetForm.patchValue({
      name: name ?? '',
      code: code ?? '',
      quantity: quantity ?? 1,
      acquired_at: acquiredAt ?? '',
    });
  }

  private async runLegacyAutoExport(): Promise<void> {
    if (this.legacyAutoExportHandled) {
      return;
    }

    this.legacyAutoExportHandled = true;

    switch ((this.route.snapshot.queryParamMap.get('auto_export') ?? '').trim()) {
      case 'pdf':
        await this.downloadCatalogPdf();
        break;
      case 'csv':
        await this.downloadCatalogCsv();
        break;
      default:
        break;
    }
  }

  private normalizeNumber(value: string | null): number | null {
    if (value === null) {
      return null;
    }

    const normalized = Number(value);

    return Number.isFinite(normalized) ? normalized : null;
  }

  private triggerFileDownload(blob: Blob, fileName: string): void {
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = fileName;
    link.click();
    URL.revokeObjectURL(objectUrl);
  }

  private buildCatalogFileName(baseName: string, extension: 'pdf' | 'csv'): string {
    const search = this.search().trim();
    const suffix = search === '' ? '' : `-${this.safeFileNameSegment(search)}`;

    return `${baseName}${suffix}.${extension}`;
  }

  private safeFileNameSegment(value: string): string {
    const normalized = value.trim().replace(/[^A-Za-z0-9._-]+/g, '-').replace(/^[-_.]+|[-_.]+$/g, '');

    return normalized === '' ? 'inventario' : normalized;
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch ((value ?? '').trim()) {
      case 'asset-form-write':
        return 'El formulario legacy de activos fue retirado. Revisa los datos precargados y confirma el registro desde este modulo.';
      case 'asset-form-delete':
        return 'La eliminacion legacy de activos fue retirada. Usa este modulo para depurar o corregir la base nueva.';
      case 'inventory-report':
        return 'El reporte legacy de inventario ahora se descarga desde este modulo nuevo. Ya no depende del PDF antiguo del root PHP.';
      default:
        return null;
    }
  }
}
