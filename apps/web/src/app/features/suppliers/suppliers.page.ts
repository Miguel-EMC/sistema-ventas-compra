import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { DOCUMENT_TYPE_OPTIONS, BusinessPartner, BusinessPartnerPayload } from '../partners/partners.types';
import { SuppliersApiService } from './suppliers.api';

@Component({
  selector: 'app-suppliers-page',
  imports: [ReactiveFormsModule],
  template: `
    <section class="stack">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Proveedores</span>
          <h1 class="page-title">Base de abastecimiento y relacion comercial.</h1>
          <p class="page-description">
            Este modulo prepara el dominio de proveedores para compras, abastecimiento, costos y
            futuras ordenes de compra del POS nuevo.
          </p>
        </div>
        <span class="pill">Migracion real del dominio Suppliers</span>
      </header>

      @if (legacyNotice()) {
        <article class="surface surface--muted stack">
          <span class="page-kicker">Migracion</span>
          <strong>{{ legacyNotice() }}</strong>
        </article>
      }

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Proveedores totales</span>
          <strong class="metric-card__value">{{ suppliers().length }}</strong>
          <span class="metric-card__hint">Base operativa para compras y reposicion.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Activos</span>
          <strong class="metric-card__value">{{ activeSuppliersCount() }}</strong>
          <span class="metric-card__hint">Proveedores habilitados para compra.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Con documento</span>
          <strong class="metric-card__value">{{ suppliersWithDocumentCount() }}</strong>
          <span class="metric-card__hint">Terceros ya identificados formalmente.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Con contacto</span>
          <strong class="metric-card__value">{{ suppliersWithContactCount() }}</strong>
          <span class="metric-card__hint">Listos para seguimiento comercial o soporte.</span>
        </article>
      </section>

      <section class="admin-layout">
        <article class="surface stack">
          <div class="toolbar">
            <div class="field field--search">
              <label for="supplier-search">Buscar proveedores</label>
              <input
                id="supplier-search"
                type="text"
                [value]="search()"
                (input)="onSearch($event)"
                placeholder="Nombre, documento, correo o telefono"
              />
            </div>

            <div class="cta-row">
              <button class="btn" type="button" (click)="loadSuppliers()">Refrescar</button>
              @if (isAdmin()) {
                <button class="btn btn--ghost" type="button" (click)="resetForm()">Nuevo</button>
              }
            </div>
          </div>

          @if (error()) {
            <p class="alert alert--danger">{{ error() }}</p>
          }

          @if (suppliers().length === 0) {
            <p class="muted">
              Todavia no hay proveedores migrados en la plataforma nueva. Puedes registrar el
              primero desde el formulario lateral.
            </p>
          } @else {
            <div class="table-list">
              @for (supplier of suppliers(); track supplier.id) {
                <article class="table-list__row">
                  <div class="table-list__copy">
                    <strong>{{ supplier.name }}</strong>
                    <div class="badge-row">
                      <span class="pill">
                        {{ labelForDocumentType(supplier.document_type) }}
                      </span>
                      @if (supplier.document_number) {
                        <span class="pill pill--muted">{{ supplier.document_number }}</span>
                      }
                      @if (!supplier.is_active) {
                        <span class="pill pill--warning">Inactivo</span>
                      }
                    </div>
                    <p>{{ supplier.email || 'Sin correo' }} · {{ supplier.phone || 'Sin telefono' }}</p>
                    <small>{{ supplier.address || 'Sin direccion registrada' }}</small>
                  </div>

                  <div class="table-list__actions">
                    <button class="btn btn--ghost" type="button" (click)="editSupplier(supplier)">
                      Editar
                    </button>
                    @if (isAdmin()) {
                      <button class="btn" type="button" (click)="deleteSupplier(supplier)">
                        Eliminar
                      </button>
                    }
                  </div>
                </article>
              }
            </div>
          }
        </article>

        @if (isAdmin()) {
          <article class="surface stack">
            <div class="page-header">
              <div class="page-header__copy">
                <span class="page-kicker">Formulario</span>
                <h2 class="page-title">
                  @if (editingId()) {
                    Editar proveedor
                  } @else {
                    Crear proveedor
                  }
                </h2>
                <p class="page-description">
                  Este bloque deja lista la base de proveedores para integrarla luego con compras,
                  costos y reposicion de stock.
                </p>
              </div>
            </div>

            <form class="form-grid" [formGroup]="form" (ngSubmit)="submit()">
              <div class="split">
                <div class="field">
                  <label for="supplier-name">Nombre</label>
                  <input id="supplier-name" type="text" formControlName="name" />
                </div>

                <div class="field">
                  <label for="supplier-document-type">Tipo de documento</label>
                  <select id="supplier-document-type" formControlName="document_type">
                    <option value="">Sin documento</option>
                    @for (option of documentTypes; track option.value) {
                      <option [value]="option.value">{{ option.label }}</option>
                    }
                  </select>
                </div>
              </div>

              <div class="split">
                <div class="field">
                  <label for="supplier-document-number">Numero de documento</label>
                  <input id="supplier-document-number" type="text" formControlName="document_number" />
                </div>

                <div class="field">
                  <label for="supplier-email">Correo</label>
                  <input id="supplier-email" type="email" formControlName="email" />
                </div>
              </div>

              <div class="split">
                <div class="field">
                  <label for="supplier-phone">Telefono</label>
                  <input id="supplier-phone" type="text" formControlName="phone" />
                </div>

                <div class="field">
                  <label for="supplier-active">Estado</label>
                  <select id="supplier-active" formControlName="is_active">
                    <option [ngValue]="true">Activo</option>
                    <option [ngValue]="false">Inactivo</option>
                  </select>
                </div>
              </div>

              <div class="field">
                <label for="supplier-address">Direccion</label>
                <textarea id="supplier-address" formControlName="address"></textarea>
              </div>

              @if (error()) {
                <p class="alert alert--danger">{{ error() }}</p>
              }

              <div class="cta-row">
                <button class="btn btn--primary" type="submit" [disabled]="saving()">
                  @if (saving()) {
                    Guardando...
                  } @else if (editingId()) {
                    Actualizar proveedor
                  } @else {
                    Crear proveedor
                  }
                </button>
                <button class="btn btn--ghost" type="button" (click)="resetForm()">Limpiar</button>
              </div>
            </form>
          </article>
        } @else {
          <article class="surface stack">
            <span class="page-kicker">Solo lectura</span>
            <h2 class="page-title">Tu rol puede consultar la base de proveedores.</h2>
            <p class="page-description">
              La gestion de altas y cambios queda reservada a administracion, pero la consulta ya
              se puede hacer desde el panel nuevo.
            </p>
          </article>
        }
      </section>
    </section>
  `,
})
export class SuppliersPageComponent {
  private readonly auth = inject(AuthService);
  private readonly api = inject(SuppliersApiService);
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private legacyPrefillApplied = false;

  protected readonly isAdmin = this.auth.isAdmin;
  protected readonly documentTypes = DOCUMENT_TYPE_OPTIONS;
  protected readonly suppliers = signal<BusinessPartner[]>([]);
  protected readonly search = signal('');
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly editingId = signal<number | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);

  protected readonly activeSuppliersCount = computed(
    () => this.suppliers().filter((supplier) => supplier.is_active).length,
  );
  protected readonly suppliersWithDocumentCount = computed(
    () => this.suppliers().filter((supplier) => supplier.document_number !== null).length,
  );
  protected readonly suppliersWithContactCount = computed(
    () =>
      this.suppliers().filter(
        (supplier) => supplier.email !== null || supplier.phone !== null,
      ).length,
  );

  protected readonly form = this.fb.group({
    name: ['', [Validators.required]],
    document_type: [''],
    document_number: [''],
    email: [''],
    phone: [''],
    address: [''],
    is_active: [true, [Validators.required]],
  });

  public constructor() {
    this.legacyNotice.set(this.resolveLegacyNotice(this.route.snapshot.queryParamMap.get('legacy')));
    this.applyLegacyPrefillFromQuery();
    void this.loadSuppliers();
  }

  protected async loadSuppliers(): Promise<void> {
    try {
      this.error.set(null);
      this.suppliers.set(await this.api.list(this.search()));
    } catch (error) {
      this.error.set(resolveApiError(error));
    }
  }

  protected onSearch(event: Event): void {
    const target = event.target as HTMLInputElement;
    this.search.set(target.value);
    void this.loadSuppliers();
  }

  protected editSupplier(supplier: BusinessPartner): void {
    this.editingId.set(supplier.id);
    this.error.set(null);
    this.form.patchValue({
      name: supplier.name,
      document_type: supplier.document_type ?? '',
      document_number: supplier.document_number ?? '',
      email: supplier.email ?? '',
      phone: supplier.phone ?? '',
      address: supplier.address ?? '',
      is_active: supplier.is_active,
    });
  }

  protected resetForm(): void {
    this.editingId.set(null);
    this.error.set(null);
    this.form.reset({
      name: '',
      document_type: '',
      document_number: '',
      email: '',
      phone: '',
      address: '',
      is_active: true,
    });
  }

  protected async submit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    try {
      const payload = this.buildPayload();

      if (this.editingId()) {
        await this.api.update(this.editingId() as number, payload);
      } else {
        await this.api.create(payload);
      }

      this.resetForm();
      await this.loadSuppliers();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected async deleteSupplier(supplier: BusinessPartner): Promise<void> {
    if (!window.confirm(`Estas seguro de eliminar al proveedor ${supplier.name}?`)) {
      return;
    }

    try {
      await this.api.delete(supplier.id);

      if (this.editingId() === supplier.id) {
        this.resetForm();
      }

      await this.loadSuppliers();
    } catch (error) {
      this.error.set(resolveApiError(error));
    }
  }

  protected labelForDocumentType(value: string | null): string {
    if (!value) {
      return 'Sin documento';
    }

    return this.documentTypes.find((option) => option.value === value)?.label ?? value;
  }

  private buildPayload(): BusinessPartnerPayload {
    const raw = this.form.getRawValue();

    return {
      document_type: this.nullableText(raw.document_type),
      document_number: this.nullableText(raw.document_number),
      name: raw.name?.trim() ?? '',
      email: this.nullableText(raw.email),
      phone: this.nullableText(raw.phone),
      address: this.nullableText(raw.address),
      is_active: Boolean(raw.is_active),
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
    const phone = this.nullableText(query.get('phone'));
    const address = this.nullableText(query.get('address'));

    if (name === null && phone === null && address === null) {
      return;
    }

    this.resetForm();
    this.form.patchValue({
      name: name ?? '',
      phone: phone ?? '',
      address: address ?? '',
    });
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch ((value ?? '').trim()) {
      case 'supplier-form-write':
        return 'El formulario legacy de proveedores ya no escribe en la base anterior. Revisa los datos precargados y confirma el registro aqui.';
      case 'supplier-form-delete':
        return 'La eliminacion legacy de proveedores fue retirada. Usa este modulo para administrar la base nueva.';
      default:
        return null;
    }
  }
}
