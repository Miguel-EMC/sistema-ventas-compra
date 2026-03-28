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
import { resolveApiError } from '../../core/http/resolve-api-error';
import { AuthService } from '../../core/auth/auth.service';
import { SettingsApiService } from './settings.api';
import { BusinessSettings, UpdateBusinessSettingsPayload } from './settings.types';

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
          <h1 class="page-title">Empresa, moneda e idioma ya viven en el stack nuevo.</h1>
          <p class="page-description">
            Este modulo reemplaza el placeholder y centraliza perfil comercial, defaults operativos
            y parametros globales del negocio.
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

        <section class="settings-layout" [formGroup]="form">
          <mat-card appearance="outlined" class="settings-card">
            <mat-card-header>
              <mat-card-title>Perfil de empresa</mat-card-title>
              <mat-card-subtitle>Datos fiscales y comerciales principales.</mat-card-subtitle>
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
              </div>
            </mat-card-content>
          </mat-card>

          <mat-card appearance="outlined" class="settings-card">
            <mat-card-header>
              <mat-card-title>Parametros del sistema</mat-card-title>
              <mat-card-subtitle>Defaults operativos para POS, stock e idioma.</mat-card-subtitle>
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
      grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
    }

    .settings-card {
      border-radius: 1.5rem;
      height: 100%;
    }

    .settings-card mat-card-content:first-of-type {
      margin-top: 1rem;
    }

    .settings-form,
    .settings-toggle-grid {
      display: grid;
      gap: 1rem;
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

    .settings-success {
      border-color: rgba(19, 128, 77, 0.18);
      background: rgba(19, 128, 77, 0.08);
    }

    .settings-metric__value {
      font-size: 1.35rem;
      line-height: 1.25;
    }

    @media (max-width: 1100px) {
      .settings-layout,
      .settings-grid,
      .settings-grid--double,
      .settings-grid--triple {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class SettingsPageComponent {
  protected readonly auth = inject(AuthService);

  private readonly fb = inject(FormBuilder);
  private readonly settingsApi = inject(SettingsApiService);

  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly settings = signal<BusinessSettings | null>(null);

  protected readonly documentTypes = [
    { value: 'ticket', label: 'Ticket' },
    { value: 'factura', label: 'Factura' },
    { value: 'nota', label: 'Nota de venta' },
  ];

  protected readonly form = this.fb.group({
    legal_name: ['', [Validators.required]],
    trade_name: [''],
    tax_id: [''],
    email: ['', [Validators.email]],
    phone: [''],
    website: [''],
    address_line: [''],
    city: [''],
    region: [''],
    country_code: ['EC'],
    currency_code: ['USD', [Validators.required]],
    locale_code: ['es-EC', [Validators.required]],
    timezone: ['America/Guayaquil', [Validators.required]],
    tax_included_prices: [false, [Validators.required]],
    allow_negative_stock: [false, [Validators.required]],
    default_document_type: ['ticket', [Validators.required]],
    invoice_footer: [''],
  });

  public constructor() {
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
      this.successMessage.set('La configuracion del negocio fue actualizada en la API nueva.');
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

  private applySettingsToForm(settings: BusinessSettings): void {
    this.form.patchValue({
      legal_name: settings.company_profile.legal_name,
      trade_name: settings.company_profile.trade_name ?? '',
      tax_id: settings.company_profile.tax_id ?? '',
      email: settings.company_profile.email ?? '',
      phone: settings.company_profile.phone ?? '',
      website: settings.company_profile.website ?? '',
      address_line: settings.company_profile.address_line ?? '',
      city: settings.company_profile.city ?? '',
      region: settings.company_profile.region ?? '',
      country_code: settings.company_profile.country_code ?? 'EC',
      currency_code: settings.system_settings.currency_code,
      locale_code: settings.system_settings.locale_code,
      timezone: settings.system_settings.timezone,
      tax_included_prices: settings.system_settings.tax_included_prices,
      allow_negative_stock: settings.system_settings.allow_negative_stock,
      default_document_type: settings.system_settings.default_document_type,
      invoice_footer: settings.system_settings.invoice_footer ?? '',
    });
  }

  private buildPayload(): UpdateBusinessSettingsPayload {
    const raw = this.form.getRawValue();

    return {
      company_profile: {
        legal_name: raw.legal_name?.trim() ?? '',
        trade_name: this.nullableText(raw.trade_name),
        tax_id: this.nullableText(raw.tax_id),
        email: this.nullableText(raw.email),
        phone: this.nullableText(raw.phone),
        website: this.nullableText(raw.website),
        address_line: this.nullableText(raw.address_line),
        city: this.nullableText(raw.city),
        region: this.nullableText(raw.region),
        country_code: this.nullableCountry(raw.country_code),
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
    };
  }

  private syncFormAccess(): void {
    if (this.auth.isAdmin()) {
      this.form.enable({ emitEvent: false });
      return;
    }

    this.form.disable({ emitEvent: false });
  }

  private nullableText(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }

  private nullableCountry(value: string | null | undefined): string | null {
    const normalized = this.nullableText(value);

    return normalized ? normalized.toUpperCase() : null;
  }
}
