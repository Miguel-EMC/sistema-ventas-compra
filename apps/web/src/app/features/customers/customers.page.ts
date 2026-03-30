import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { DOCUMENT_TYPE_OPTIONS, BusinessPartner, BusinessPartnerPayload } from '../partners/partners.types';
import { CustomersApiService } from './customers.api';

@Component({
  selector: 'app-customers-page',
  imports: [ReactiveFormsModule],
  template: `
    <section class="stack">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Clientes</span>
          <h1 class="page-title">Base de clientes para ventas, facturacion y seguimiento.</h1>
          <p class="page-description">
            Este modulo ya deja de depender del legacy. Aqui se administran los clientes que luego
            van a alimentar checkout, historial de compra y reportes comerciales.
          </p>
        </div>
        <span class="pill">Migracion real del dominio Customers</span>
      </header>

      @if (legacyNotice()) {
        <article class="surface surface--muted stack">
          <span class="page-kicker">Migracion</span>
          <strong>{{ legacyNotice() }}</strong>
        </article>
      }

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Clientes totales</span>
          <strong class="metric-card__value">{{ customers().length }}</strong>
          <span class="metric-card__hint">Base de terceros lista para el POS nuevo.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Activos</span>
          <strong class="metric-card__value">{{ activeCustomersCount() }}</strong>
          <span class="metric-card__hint">Clientes disponibles para operar sin bloqueo.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Con documento</span>
          <strong class="metric-card__value">{{ customersWithDocumentCount() }}</strong>
          <span class="metric-card__hint">Listos para validacion comercial o factura.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Con contacto</span>
          <strong class="metric-card__value">{{ customersWithContactCount() }}</strong>
          <span class="metric-card__hint">Clientes con correo o telefono registrado.</span>
        </article>
      </section>

      <section class="admin-layout">
        <article class="surface stack">
          <div class="toolbar">
            <div class="field field--search">
              <label for="customer-search">Buscar clientes</label>
              <input
                id="customer-search"
                type="text"
                [value]="search()"
                (input)="onSearch($event)"
                placeholder="Nombre, documento, correo o telefono"
              />
            </div>

            <div class="cta-row">
              <button class="btn" type="button" (click)="loadCustomers()">Refrescar</button>
              @if (isAdmin()) {
                <button class="btn btn--ghost" type="button" (click)="resetForm()">Nuevo</button>
              }
            </div>
          </div>

          @if (error()) {
            <p class="alert alert--danger">{{ error() }}</p>
          }

          @if (customers().length === 0) {
            <p class="muted">
              Todavia no hay clientes migrados en la plataforma nueva. Puedes crear el primero
              desde el formulario lateral.
            </p>
          } @else {
            <div class="table-list">
              @for (customer of customers(); track customer.id) {
                <article class="table-list__row">
                  <div class="table-list__copy">
                    <strong>{{ customer.name }}</strong>
                    <div class="badge-row">
                      <span class="pill">
                        {{ labelForDocumentType(customer.document_type) }}
                      </span>
                      @if (customer.document_number) {
                        <span class="pill pill--muted">{{ customer.document_number }}</span>
                      }
                      @if (!customer.is_active) {
                        <span class="pill pill--warning">Inactivo</span>
                      }
                    </div>
                    <p>{{ customer.email || 'Sin correo' }} · {{ customer.phone || 'Sin telefono' }}</p>
                    <small>{{ customer.address || 'Sin direccion registrada' }}</small>
                  </div>

                  <div class="table-list__actions">
                    <button class="btn btn--ghost" type="button" (click)="editCustomer(customer)">
                      Editar
                    </button>
                    @if (isAdmin()) {
                      <button class="btn" type="button" (click)="deleteCustomer(customer)">
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
                    Editar cliente
                  } @else {
                    Crear cliente
                  }
                </h2>
                <p class="page-description">
                  Este bloque deja listo el dominio de clientes para relacionarlo despues con
                  facturacion, borradores de venta y analitica comercial.
                </p>
              </div>
            </div>

            <form class="form-grid" [formGroup]="form" (ngSubmit)="submit()">
              <div class="split">
                <div class="field">
                  <label for="customer-name">Nombre</label>
                  <input id="customer-name" type="text" formControlName="name" />
                </div>

                <div class="field">
                  <label for="customer-document-type">Tipo de documento</label>
                  <select id="customer-document-type" formControlName="document_type">
                    <option value="">Sin documento</option>
                    @for (option of documentTypes; track option.value) {
                      <option [value]="option.value">{{ option.label }}</option>
                    }
                  </select>
                </div>
              </div>

              <div class="split">
                <div class="field">
                  <label for="customer-document-number">Numero de documento</label>
                  <input id="customer-document-number" type="text" formControlName="document_number" />
                </div>

                <div class="field">
                  <label for="customer-email">Correo</label>
                  <input id="customer-email" type="email" formControlName="email" />
                </div>
              </div>

              <div class="split">
                <div class="field">
                  <label for="customer-phone">Telefono</label>
                  <input id="customer-phone" type="text" formControlName="phone" />
                </div>

                <div class="field">
                  <label for="customer-active">Estado</label>
                  <select id="customer-active" formControlName="is_active">
                    <option [ngValue]="true">Activo</option>
                    <option [ngValue]="false">Inactivo</option>
                  </select>
                </div>
              </div>

              <div class="field">
                <label for="customer-address">Direccion</label>
                <textarea id="customer-address" formControlName="address"></textarea>
              </div>

              @if (error()) {
                <p class="alert alert--danger">{{ error() }}</p>
              }

              <div class="cta-row">
                <button class="btn btn--primary" type="submit" [disabled]="saving()">
                  @if (saving()) {
                    Guardando...
                  } @else if (editingId()) {
                    Actualizar cliente
                  } @else {
                    Crear cliente
                  }
                </button>
                <button class="btn btn--ghost" type="button" (click)="resetForm()">Limpiar</button>
              </div>
            </form>
          </article>
        } @else {
          <article class="surface stack">
            <span class="page-kicker">Solo lectura</span>
            <h2 class="page-title">Tu rol puede consultar la base de clientes.</h2>
            <p class="page-description">
              La administracion de altas y cambios queda reservada a perfiles de gestion, pero la
              consulta ya se puede hacer desde este frontend nuevo.
            </p>
          </article>
        }
      </section>
    </section>
  `,
})
export class CustomersPageComponent {
  private readonly auth = inject(AuthService);
  private readonly api = inject(CustomersApiService);
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private legacyPrefillApplied = false;

  protected readonly isAdmin = this.auth.isAdmin;
  protected readonly documentTypes = DOCUMENT_TYPE_OPTIONS;
  protected readonly customers = signal<BusinessPartner[]>([]);
  protected readonly search = signal('');
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly editingId = signal<number | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);

  protected readonly activeCustomersCount = computed(
    () => this.customers().filter((customer) => customer.is_active).length,
  );
  protected readonly customersWithDocumentCount = computed(
    () => this.customers().filter((customer) => customer.document_number !== null).length,
  );
  protected readonly customersWithContactCount = computed(
    () =>
      this.customers().filter(
        (customer) => customer.email !== null || customer.phone !== null,
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
    void this.loadCustomers();
  }

  protected async loadCustomers(): Promise<void> {
    try {
      this.error.set(null);
      this.customers.set(await this.api.list(this.search()));
    } catch (error) {
      this.error.set(resolveApiError(error));
    }
  }

  protected onSearch(event: Event): void {
    const target = event.target as HTMLInputElement;
    this.search.set(target.value);
    void this.loadCustomers();
  }

  protected editCustomer(customer: BusinessPartner): void {
    this.editingId.set(customer.id);
    this.error.set(null);
    this.form.patchValue({
      name: customer.name,
      document_type: customer.document_type ?? '',
      document_number: customer.document_number ?? '',
      email: customer.email ?? '',
      phone: customer.phone ?? '',
      address: customer.address ?? '',
      is_active: customer.is_active,
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
      await this.loadCustomers();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected async deleteCustomer(customer: BusinessPartner): Promise<void> {
    if (!window.confirm(`Estas seguro de eliminar al cliente ${customer.name}?`)) {
      return;
    }

    try {
      await this.api.delete(customer.id);

      if (this.editingId() === customer.id) {
        this.resetForm();
      }

      await this.loadCustomers();
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
    const documentNumber = this.nullableText(query.get('document_number'));
    const email = this.nullableText(query.get('email'));
    const phone = this.nullableText(query.get('phone'));
    const address = this.nullableText(query.get('address'));

    if (name === null && documentNumber === null && email === null && phone === null && address === null) {
      return;
    }

    this.resetForm();
    this.form.patchValue({
      name: name ?? '',
      document_number: documentNumber ?? '',
      email: email ?? '',
      phone: phone ?? '',
      address: address ?? '',
    });
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch ((value ?? '').trim()) {
      case 'customer-lookup-retired':
        return 'La busqueda AJAX legacy de clientes fue retirada. Usa este modulo como base de consulta y mantenimiento.';
      case 'customer-form-write':
        return 'El formulario legacy de clientes ya no escribe en la base anterior. Revisa los datos precargados y guarda desde aqui.';
      case 'customer-form-delete':
        return 'La eliminacion legacy de clientes fue retirada. Usa este modulo para depurar o corregir la base nueva.';
      default:
        return null;
    }
  }
}
