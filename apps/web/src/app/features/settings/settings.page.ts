import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatDividerModule } from '@angular/material/divider';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSelectModule } from '@angular/material/select';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { ActivatedRoute } from '@angular/router';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { AuthService } from '../../core/auth/auth.service';
import {
  BusinessSettings,
  TaxResolution,
  UpdateBusinessSettingsPayload,
} from './settings.types';
import { SettingsApiService } from './settings.api';

@Component({
  selector: 'app-settings-page',
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatCardModule,
    MatChipsModule,
    MatDividerModule,
    MatFormFieldModule,
    MatInputModule,
    MatProgressBarModule,
    MatSelectModule,
    MatSlideToggleModule,
  ],
  template: `
    <section class="stack settings-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Configuracion</span>
          <h1 class="page-title">Negocio, preferencias operativas y configuracion fiscal.</h1>
          <p class="page-description">
            Centraliza la informacion clave de la empresa, el comportamiento del sistema y la
            configuracion necesaria para emitir comprobantes.
          </p>
        </div>
        <span class="pill">
          @if (auth.isAdmin()) {
            Edicion habilitada
          } @else {
            Solo lectura
          }
        </span>
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
        <article class="surface stack settings-success">
          <span class="page-kicker">Configuracion</span>
          <strong>{{ successMessage() }}</strong>
        </article>
      }

      @if (legacyNotice()) {
        <article class="surface surface--muted stack">
          <span class="page-kicker">Migracion</span>
          <strong>{{ legacyNotice() }}</strong>
        </article>
      }

      @if (settings(); as settings) {
        <section class="grid grid--cards">
          <article class="surface metric-card">
            <span class="metric-card__label">Empresa principal</span>
            <strong class="metric-card__value settings-metric__value">
              {{ settings.company_profile.trade_name || settings.company_profile.legal_name }}
            </strong>
            <span class="metric-card__hint">Perfil base usado por la plataforma nueva.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Moneda activa</span>
            <strong class="metric-card__value settings-metric__value">
              {{ settings.currency?.symbol || '$' }} · {{ settings.currency?.code || 'USD' }}
            </strong>
            <span class="metric-card__hint">
              {{ settings.currency?.name || 'Moneda por defecto del sistema' }}
            </span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Idioma</span>
            <strong class="metric-card__value settings-metric__value">
              {{ settings.locale?.code || 'es-EC' }}
            </strong>
            <span class="metric-card__hint">{{ settings.locale?.name || 'Locale principal' }}</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Documento por defecto</span>
            <strong class="metric-card__value settings-metric__value">
              {{ labelForDocument(settings.system_settings.default_document_type) }}
            </strong>
            <span class="metric-card__hint">Valor inicial para el checkout del POS nuevo.</span>
          </article>

          <article class="surface metric-card">
            <span class="metric-card__label">Dosificacion activa</span>
            <strong class="metric-card__value settings-metric__value">
              {{ settings.active_tax_resolution?.series || 'Sin serie activa' }}
            </strong>
            <span class="metric-card__hint">
              @if (settings.active_tax_resolution) {
                {{ settings.active_tax_resolution.authorization_number }} · siguiente
                {{ settings.active_tax_resolution.next_invoice_number }}
              } @else {
                No hay resolucion habilitada para facturas.
              }
            </span>
          </article>
        </section>

        @if (!auth.isAdmin()) {
          <article class="surface surface--muted stack">
            <span class="page-kicker">Permisos</span>
            <p class="page-description">
              Tu usuario puede consultar la configuracion actual, pero solo administracion puede
              cambiarla.
            </p>
          </article>
        }

        @if (!settings.active_tax_resolution) {
          <article class="surface surface--muted stack settings-warning">
            <span class="page-kicker">Facturacion</span>
            <strong>No hay una dosificacion activa. El POS bloqueara facturas hasta configurarla.</strong>
          </article>
        }

        <article class="surface stack">
          <div class="settings-sections">
            <button class="settings-section" type="button" [class.is-active]="settingsSection() === 'company'" (click)="setSettingsSection('company')">
              Negocio
            </button>
            <button class="settings-section" type="button" [class.is-active]="settingsSection() === 'system'" (click)="setSettingsSection('system')">
              Operacion
            </button>
            <button class="settings-section" type="button" [class.is-active]="settingsSection() === 'billing'" (click)="setSettingsSection('billing')">
              Fiscal
            </button>
          </div>
        </article>

        <section class="settings-layout" [formGroup]="form">
          @if (settingsSection() === 'company') {
          <mat-card appearance="outlined" class="settings-card settings-card--wide">
            <mat-card-header>
              <mat-card-title>Datos del negocio</mat-card-title>
              <mat-card-subtitle>Identidad comercial y datos principales de la empresa.</mat-card-subtitle>
            </mat-card-header>

            <mat-card-content class="stack">
              <div class="settings-form">
                <div class="settings-grid">
                  <mat-form-field appearance="outline">
                    <mat-label>Razon social</mat-label>
                    <input matInput type="text" formControlName="legal_name" />
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Nombre comercial</mat-label>
                    <input matInput type="text" formControlName="trade_name" />
                  </mat-form-field>
                </div>

                <div class="settings-grid settings-grid--triple">
                  <mat-form-field appearance="outline">
                    <mat-label>RUC / NIT</mat-label>
                    <input matInput type="text" formControlName="tax_id" />
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Correo</mat-label>
                    <input matInput type="email" formControlName="email" />
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Telefono</mat-label>
                    <input matInput type="text" formControlName="phone" />
                  </mat-form-field>
                </div>

                <div class="settings-grid settings-grid--triple">
                  <mat-form-field appearance="outline">
                    <mat-label>Sitio web</mat-label>
                    <input matInput type="text" formControlName="website" />
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Pais</mat-label>
                    <input matInput type="text" maxlength="2" formControlName="country_code" />
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Ciudad</mat-label>
                    <input matInput type="text" formControlName="city" />
                  </mat-form-field>
                </div>

                <div class="settings-grid">
                  <mat-form-field appearance="outline">
                    <mat-label>Direccion</mat-label>
                    <input matInput type="text" formControlName="address_line" />
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Region / Provincia</mat-label>
                    <input matInput type="text" formControlName="region" />
                  </mat-form-field>
                </div>

                <mat-divider></mat-divider>

                <div class="settings-grid settings-grid--double">
                  <mat-form-field appearance="outline">
                    <mat-label>Propietario en factura</mat-label>
                    <input matInput type="text" formControlName="billing_owner_name" />
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Numero / referencia</mat-label>
                    <input matInput type="text" formControlName="billing_address_reference" />
                  </mat-form-field>
                </div>
              </div>
            </mat-card-content>
          </mat-card>
          }

          @if (settingsSection() === 'system') {
          <mat-card appearance="outlined" class="settings-card">
            <mat-card-header>
              <mat-card-title>Preferencias operativas</mat-card-title>
              <mat-card-subtitle>Moneda, idioma y reglas base del funcionamiento diario.</mat-card-subtitle>
            </mat-card-header>

            <mat-card-content class="stack">
              <div class="settings-grid settings-grid--triple">
                <mat-form-field appearance="outline">
                  <mat-label>Moneda</mat-label>
                  <mat-select formControlName="currency_code">
                    @for (currency of settings.currencies; track currency.code) {
                      <mat-option [value]="currency.code">
                        {{ currency.symbol }} · {{ currency.code }} · {{ currency.name }}
                      </mat-option>
                    }
                  </mat-select>
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Idioma</mat-label>
                  <mat-select formControlName="locale_code">
                    @for (locale of settings.locales; track locale.code) {
                      <mat-option [value]="locale.code">
                        {{ locale.code }} · {{ locale.name }}
                      </mat-option>
                    }
                  </mat-select>
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Zona horaria</mat-label>
                  <input matInput type="text" formControlName="timezone" />
                </mat-form-field>
              </div>

              <div class="settings-grid settings-grid--double">
                <mat-form-field appearance="outline">
                  <mat-label>Documento por defecto</mat-label>
                  <mat-select formControlName="default_document_type">
                    @for (option of documentTypes; track option.value) {
                      <mat-option [value]="option.value">{{ option.label }}</mat-option>
                    }
                  </mat-select>
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Pie de comprobante</mat-label>
                  <textarea matInput rows="3" formControlName="invoice_footer"></textarea>
                </mat-form-field>
              </div>

              <mat-divider></mat-divider>

              <div class="settings-toggle-grid">
                <mat-slide-toggle formControlName="tax_included_prices">
                  Los precios incluyen impuesto
                </mat-slide-toggle>
                <mat-slide-toggle formControlName="allow_negative_stock">
                  Permitir stock negativo
                </mat-slide-toggle>
              </div>

              <mat-chip-set>
                <mat-chip>{{ settings.currency?.code || form.getRawValue().currency_code }}</mat-chip>
                <mat-chip>{{ settings.locale?.code || form.getRawValue().locale_code }}</mat-chip>
                <mat-chip>{{ labelForDocument(form.getRawValue().default_document_type || 'ticket') }}</mat-chip>
              </mat-chip-set>
            </mat-card-content>
          </mat-card>
          }

          @if (settingsSection() === 'billing') {
          <mat-card appearance="outlined" class="settings-card settings-card--billing">
            <mat-card-header>
              <mat-card-title>Comprobantes fiscales</mat-card-title>
              <mat-card-subtitle>
                Resoluciones, numeracion y parametros necesarios para emitir facturas.
              </mat-card-subtitle>
            </mat-card-header>

            <mat-card-content class="stack">
              <p class="muted">
                Edita solo lo necesario. Si dejas un campo de texto vacio, se conserva el valor actual.
              </p>

              <div class="settings-inline-actions">
                @if (auth.isAdmin()) {
                  <button mat-stroked-button type="button" (click)="startNewTaxResolution()">
                    Nueva dosificacion
                  </button>
                  <button mat-stroked-button type="button" (click)="clearTaxResolutionSelection()">
                    Dejar sin activa
                  </button>
                }
              </div>

              <div class="settings-grid settings-grid--triple">
                <mat-form-field appearance="outline">
                  <mat-label>Nombre interno</mat-label>
                  <input matInput type="text" formControlName="tax_resolution_name" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Nro. autorizacion</mat-label>
                  <input matInput type="text" formControlName="tax_authorization_number" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Serie</mat-label>
                  <input matInput type="text" formControlName="tax_series" />
                </mat-form-field>
              </div>

              <div class="settings-grid settings-grid--triple">
                <mat-form-field appearance="outline">
                  <mat-label>Inicio numeracion</mat-label>
                  <input matInput type="number" min="1" formControlName="invoice_number_start" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Fin numeracion</mat-label>
                  <input matInput type="number" min="1" formControlName="invoice_number_end" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Siguiente numero</mat-label>
                  <input matInput type="number" min="1" formControlName="next_invoice_number" />
                </mat-form-field>
              </div>

              <div class="settings-grid settings-grid--double">
                <mat-form-field appearance="outline">
                  <mat-label>Inicio vigencia</mat-label>
                  <input matInput type="datetime-local" formControlName="tax_starts_at" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Fin vigencia</mat-label>
                  <input matInput type="datetime-local" formControlName="tax_ends_at" />
                </mat-form-field>
              </div>

              <div class="settings-grid settings-grid--double">
                <mat-form-field appearance="outline">
                  <mat-label>Llave tecnica</mat-label>
                  <input matInput type="text" formControlName="tax_technical_key" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Leyenda fiscal</mat-label>
                  <textarea matInput rows="3" formControlName="tax_legend"></textarea>
                </mat-form-field>
              </div>

              <mat-chip-set>
                <mat-chip>
                  Siguiente {{ formatTaxSequence(form.getRawValue().next_invoice_number) }}
                </mat-chip>
                <mat-chip>Restantes {{ remainingTaxNumbers() }}</mat-chip>
                @if (form.getRawValue().tax_series) {
                  <mat-chip>{{ form.getRawValue().tax_series }}</mat-chip>
                }
              </mat-chip-set>

              @if (settings.tax_resolutions.length > 0) {
                <mat-divider></mat-divider>

                <div class="resolution-history">
                  <div class="resolution-history__header">
                    <span class="page-kicker">Historial</span>
                    <strong>{{ settings.tax_resolutions.length }} resolucion(es) registradas</strong>
                  </div>

                  <div class="resolution-history__list">
                    @for (resolution of settings.tax_resolutions; track resolution.id) {
                      <article class="resolution-history__item">
                        <div class="resolution-history__copy">
                          <strong>{{ resolution.name }}</strong>
                          <span>
                            {{ resolution.authorization_number }}
                            @if (resolution.series) {
                              · {{ resolution.series }}
                            }
                          </span>
                          <small>
                            Siguiente {{ formatTaxSequence(resolution.next_invoice_number) }} ·
                            Restantes {{ resolution.remaining_invoices }} ·
                            {{ resolution.is_active ? 'Activa' : 'Historica' }}
                          </small>
                        </div>

                        @if (auth.isAdmin()) {
                          <button mat-stroked-button type="button" (click)="loadTaxResolution(resolution)">
                            Cargar
                          </button>
                        }
                      </article>
                    }
                  </div>
                </div>
              }

              <div class="cta-row">
                <button mat-stroked-button type="button" (click)="load()" [disabled]="loading()">
                  Recargar
                </button>
                @if (auth.isAdmin()) {
                  <button mat-flat-button color="primary" type="button" (click)="save()" [disabled]="saving()">
                    Guardar cambios
                  </button>
                }
              </div>
            </mat-card-content>
          </mat-card>
          }
        </section>
      }
    </section>
  `,
  styles: `
    .settings-page {
      align-content: start;
    }

    .settings-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
    }

    .settings-card {
      border-radius: 1.5rem;
      height: 100%;
    }

    .settings-card--wide {
      min-width: 0;
    }

    .settings-card--billing {
      grid-column: 1 / -1;
    }

    .settings-card mat-card-content:first-of-type {
      margin-top: 1rem;
    }

    .settings-form,
    .settings-toggle-grid,
    .resolution-history,
    .resolution-history__list {
      display: grid;
      gap: 1rem;
    }

    .settings-sections {
      display: flex;
      flex-wrap: wrap;
      gap: 0.65rem;
    }

    .settings-section {
      border: 1px solid rgba(148, 163, 184, 0.22);
      border-radius: 999px;
      background: rgba(246, 249, 252, 0.95);
      color: var(--text-muted);
      font-weight: 700;
      min-height: 2.8rem;
      padding: 0 1rem;
    }

    .settings-section.is-active {
      border-color: rgba(22, 138, 87, 0.26);
      background: rgba(22, 138, 87, 0.1);
      color: var(--primary-strong);
    }

    .settings-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .settings-grid--double {
      grid-template-columns: minmax(16rem, 0.9fr) minmax(0, 1.1fr);
    }

    .settings-grid--triple {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .settings-inline-actions {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .settings-success {
      border-color: rgba(19, 128, 77, 0.18);
      background: rgba(19, 128, 77, 0.08);
    }

    .settings-warning {
      border-color: rgba(180, 83, 9, 0.18);
      background: rgba(180, 83, 9, 0.08);
    }

    .settings-metric__value {
      font-size: 1.35rem;
      line-height: 1.25;
    }

    .resolution-history__header {
      display: grid;
      gap: 0.2rem;
    }

    .resolution-history__item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: rgba(15, 76, 129, 0.03);
      padding: 1rem;
    }

    .resolution-history__copy {
      display: grid;
      gap: 0.25rem;
      min-width: 0;
    }

    .resolution-history__copy span,
    .resolution-history__copy small {
      color: var(--text-muted);
    }

    @media (max-width: 1100px) {
      .settings-layout,
      .settings-grid,
      .settings-grid--double,
      .settings-grid--triple {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 720px) {
      .resolution-history__item {
        align-items: start;
        flex-direction: column;
      }
    }
  `,
})
export class SettingsPageComponent {
  protected readonly auth = inject(AuthService);

  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly settingsApi = inject(SettingsApiService);

  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);
  protected readonly settings = signal<BusinessSettings | null>(null);
  protected readonly settingsSection = signal<'company' | 'system' | 'billing'>('company');
  protected readonly taxResolutionCleared = signal(false);

  protected readonly documentTypes = [
    { value: 'ticket', label: 'Ticket' },
    { value: 'factura', label: 'Factura' },
    { value: 'nota', label: 'Nota de venta' },
  ];

  protected readonly form = this.fb.group({
    legal_name: [''],
    trade_name: [''],
    tax_id: [''],
    email: ['', [Validators.email]],
    phone: [''],
    website: [''],
    address_line: [''],
    city: [''],
    region: [''],
    billing_owner_name: [''],
    billing_address_reference: [''],
    country_code: [''],
    currency_code: ['USD', [Validators.required]],
    locale_code: ['es-EC', [Validators.required]],
    timezone: ['America/Guayaquil', [Validators.required]],
    tax_included_prices: [false, [Validators.required]],
    allow_negative_stock: [false, [Validators.required]],
    default_document_type: ['ticket', [Validators.required]],
    invoice_footer: [''],
    tax_resolution_id: [''],
    tax_resolution_name: [''],
    tax_authorization_number: [''],
    tax_series: [''],
    invoice_number_start: [null as number | null],
    invoice_number_end: [null as number | null],
    next_invoice_number: [null as number | null],
    tax_starts_at: [''],
    tax_ends_at: [''],
    tax_technical_key: [''],
    tax_legend: [''],
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
      const settings = await this.settingsApi.getBusinessSettings();
      this.settings.set(settings);
      this.successMessage.set(null);
      this.applySettingsToForm(settings);
      this.syncFormAccess();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected async save(): Promise<void> {
    if (!this.auth.isAdmin()) {
      return;
    }

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const settings = await this.settingsApi.updateBusinessSettings(this.buildPayload());
      this.settings.set(settings);
      this.successMessage.set(
        'La configuracion del negocio y la dosificacion activa fueron actualizadas en la API nueva.',
      );
      this.applySettingsToForm(settings);
      this.syncFormAccess();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected labelForDocument(value: string): string {
    return this.documentTypes.find((option) => option.value === value)?.label ?? value;
  }

  protected setSettingsSection(section: 'company' | 'system' | 'billing'): void {
    this.settingsSection.set(section);
  }

  protected loadTaxResolution(resolution: TaxResolution): void {
    this.taxResolutionCleared.set(false);
    this.form.patchValue({
      tax_resolution_id: String(resolution.id),
      tax_resolution_name: '',
      tax_authorization_number: '',
      tax_series: '',
      invoice_number_start: null,
      invoice_number_end: null,
      next_invoice_number: null,
      tax_starts_at: '',
      tax_ends_at: '',
      tax_technical_key: '',
      tax_legend: '',
    });
  }

  protected startNewTaxResolution(): void {
    if (!this.auth.isAdmin()) {
      return;
    }

    this.taxResolutionCleared.set(false);
    this.form.patchValue({
      tax_resolution_id: '',
      tax_resolution_name: '',
      tax_authorization_number: '',
      tax_series: '',
      invoice_number_start: 1,
      invoice_number_end: 99999999,
      next_invoice_number: 1,
      tax_starts_at: this.toDateTimeLocalValue(new Date().toISOString()),
      tax_ends_at: '',
      tax_technical_key: '',
      tax_legend: '',
    });
  }

  protected clearTaxResolutionSelection(): void {
    if (!this.auth.isAdmin()) {
      return;
    }

    this.taxResolutionCleared.set(true);
    this.form.patchValue({
      tax_resolution_id: '',
      tax_resolution_name: '',
      tax_authorization_number: '',
      tax_series: '',
      invoice_number_start: 1,
      invoice_number_end: 1,
      next_invoice_number: 1,
      tax_starts_at: '',
      tax_ends_at: '',
      tax_technical_key: '',
      tax_legend: '',
    });
  }

  protected remainingTaxNumbers(): number {
    const raw = this.form.getRawValue();
    const currentResolution = this.selectedTaxResolution();
    const end = Number(raw.invoice_number_end ?? currentResolution?.invoice_number_end ?? 0);
    const next = Number(raw.next_invoice_number ?? currentResolution?.next_invoice_number ?? 0);

    if (!Number.isFinite(end) || !Number.isFinite(next)) {
      return 0;
    }

    return Math.max(0, end - next + 1);
  }

  protected formatTaxSequence(value: number | string | null | undefined): string {
    const numeric = Number(value ?? 0);

    if (!Number.isFinite(numeric) || numeric <= 0) {
      return '00000000';
    }

    return String(Math.trunc(numeric)).padStart(8, '0');
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return 'Sin fecha';
    }

    return this.dateTimeFormatter.format(new Date(value));
  }

  private applySettingsToForm(settings: BusinessSettings): void {
    this.taxResolutionCleared.set(false);
    this.form.reset({
      legal_name: '',
      trade_name: '',
      tax_id: '',
      email: '',
      phone: '',
      website: '',
      address_line: '',
      city: '',
      region: '',
      billing_owner_name: '',
      billing_address_reference: '',
      country_code: '',
      currency_code: settings.system_settings.currency_code,
      locale_code: settings.system_settings.locale_code,
      timezone: settings.system_settings.timezone,
      tax_included_prices: settings.system_settings.tax_included_prices,
      allow_negative_stock: settings.system_settings.allow_negative_stock,
      default_document_type: settings.system_settings.default_document_type,
      invoice_footer: '',
      tax_resolution_id: settings.active_tax_resolution ? String(settings.active_tax_resolution.id) : '',
      tax_resolution_name: '',
      tax_authorization_number: '',
      tax_series: '',
      invoice_number_start: null,
      invoice_number_end: null,
      next_invoice_number: null,
      tax_starts_at: '',
      tax_ends_at: '',
      tax_technical_key: '',
      tax_legend: '',
    });
  }

  private buildPayload(): UpdateBusinessSettingsPayload {
    const raw = this.form.getRawValue();
    const settings = this.settings();

    if (!settings) {
      throw new Error('Settings not loaded');
    }

    const currentResolution = this.selectedTaxResolution();
    const hasTaxResolutionDraft = [
      raw.tax_resolution_name,
      raw.tax_authorization_number,
      raw.tax_series,
      raw.tax_starts_at,
      raw.tax_ends_at,
      raw.tax_technical_key,
      raw.tax_legend,
    ].some((value) => this.nullableText(value) !== null);

    const numericResolutionData =
      Number(raw.invoice_number_start ?? currentResolution?.invoice_number_start ?? 0) > 1 ||
      Number(raw.invoice_number_end ?? currentResolution?.invoice_number_end ?? 0) > 1 ||
      Number(raw.next_invoice_number ?? currentResolution?.next_invoice_number ?? 0) > 1;

    const shouldPersistResolution =
      !this.taxResolutionCleared() &&
      (currentResolution !== null || hasTaxResolutionDraft || numericResolutionData);

    return {
      company_profile: {
        legal_name:
          this.nullableText(raw.legal_name) ?? settings.company_profile.legal_name,
        trade_name:
          this.nullableText(raw.trade_name) ?? settings.company_profile.trade_name,
        tax_id: this.nullableText(raw.tax_id) ?? settings.company_profile.tax_id,
        email: this.nullableText(raw.email) ?? settings.company_profile.email,
        phone: this.nullableText(raw.phone) ?? settings.company_profile.phone,
        website: this.nullableText(raw.website) ?? settings.company_profile.website,
        address_line:
          this.nullableText(raw.address_line) ?? settings.company_profile.address_line,
        city: this.nullableText(raw.city) ?? settings.company_profile.city,
        region: this.nullableText(raw.region) ?? settings.company_profile.region,
        country_code:
          this.nullableCountry(raw.country_code) ?? settings.company_profile.country_code,
        metadata: {
          billing_owner_name:
            this.nullableText(raw.billing_owner_name) ??
            settings.company_profile.metadata?.billing_owner_name ??
            null,
          billing_address_reference:
            this.nullableText(raw.billing_address_reference) ??
            settings.company_profile.metadata?.billing_address_reference ??
            null,
        },
      },
      system_settings: {
        currency_code: raw.currency_code?.trim() || 'USD',
        locale_code: raw.locale_code?.trim() || 'es-EC',
        timezone: raw.timezone?.trim() || 'America/Guayaquil',
        tax_included_prices: !!raw.tax_included_prices,
        allow_negative_stock: !!raw.allow_negative_stock,
        default_document_type: (raw.default_document_type || 'ticket') as 'ticket' | 'factura' | 'nota',
        invoice_footer: this.nullableText(raw.invoice_footer),
      },
      active_tax_resolution:
        shouldPersistResolution
          ? {
              ...(currentResolution?.id ? { id: currentResolution.id } : {}),
              name: this.nullableText(raw.tax_resolution_name) ?? currentResolution?.name ?? '',
              authorization_number:
                this.nullableText(raw.tax_authorization_number) ??
                currentResolution?.authorization_number ??
                '',
              series: this.nullableText(raw.tax_series) ?? currentResolution?.series ?? null,
              invoice_number_start: Number(
                raw.invoice_number_start ?? currentResolution?.invoice_number_start ?? 1,
              ),
              invoice_number_end: Number(
                raw.invoice_number_end ?? currentResolution?.invoice_number_end ?? 1,
              ),
              next_invoice_number: Number(
                raw.next_invoice_number ?? currentResolution?.next_invoice_number ?? 1,
              ),
              starts_at:
                this.nullableText(raw.tax_starts_at) ?? currentResolution?.starts_at ?? '',
              ends_at:
                this.nullableText(raw.tax_ends_at) ?? currentResolution?.ends_at ?? null,
              technical_key:
                this.nullableText(raw.tax_technical_key) ?? currentResolution?.technical_key ?? null,
              legend: this.nullableText(raw.tax_legend) ?? currentResolution?.legend ?? null,
            }
          : null,
    };
  }

  private selectedTaxResolution(): TaxResolution | null {
    if (this.taxResolutionCleared()) {
      return null;
    }

    const settings = this.settings();

    if (!settings) {
      return null;
    }

    const selectedId = Number(this.form.getRawValue().tax_resolution_id || 0);

    if (selectedId > 0) {
      return settings.tax_resolutions.find((resolution) => resolution.id === selectedId) ?? null;
    }

    return settings.active_tax_resolution ?? null;
  }

  private syncFormAccess(): void {
    if (this.auth.isAdmin()) {
      this.form.enable({ emitEvent: false });
      return;
    }

    this.form.disable({ emitEvent: false });
  }

  private nullableText(value: string | number | null | undefined): string | null {
    const normalized = String(value ?? '').trim();

    return normalized === '' ? null : normalized;
  }

  private nullableCountry(value: string | null | undefined): string | null {
    const normalized = this.nullableText(value);

    return normalized ? normalized.toUpperCase() : null;
  }

  private resolveLegacyNotice(value: string | null): string | null {
    return (
      {
        'invoice-data': 'Los datos fijos de factura del sistema legacy ahora se administran desde este Settings nuevo.',
        language: 'El idioma general ahora se administra desde Settings y ya no depende del menu legacy.',
        currency: 'La moneda operativa ahora se administra desde Settings con catalogo actualizado en la API nueva.',
      }[value ?? ''] ?? null
    );
  }

  private toDateTimeLocalValue(value: string | null): string {
    if (!value) {
      return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
      return '';
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
  }
}
