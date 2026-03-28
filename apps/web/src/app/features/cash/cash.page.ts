import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatDividerModule } from '@angular/material/divider';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatListModule } from '@angular/material/list';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSelectModule } from '@angular/material/select';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { CashApiService } from './cash.api';
import { CashRegister, CashSession } from './cash.types';

@Component({
  selector: 'app-cash-page',
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatCardModule,
    MatChipsModule,
    MatDividerModule,
    MatFormFieldModule,
    MatInputModule,
    MatListModule,
    MatProgressBarModule,
    MatSelectModule,
  ],
  template: `
    <section class="stack cash-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Caja</span>
          <h1 class="page-title">Apertura y cierre de sesiones operativas.</h1>
          <p class="page-description">
            Este modulo completa el flujo del POS nuevo: primero se abre caja, luego se vende y al
            final se hace el cierre con una referencia clara por sesión.
          </p>
        </div>
        <span class="pill">Fase 3 del roadmap</span>
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

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Caja activa</span>
          <strong class="metric-card__value">{{ currentSession() ? 'Abierta' : 'Sin abrir' }}</strong>
          <span class="metric-card__hint">Estado de la sesión operativa del usuario actual.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Monto de apertura</span>
          <strong class="metric-card__value">
            {{ formatCurrency(currentSession()?.opening_amount ?? 0) }}
          </strong>
          <span class="metric-card__hint">Base inicial declarada para la caja activa.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Ventas asociadas</span>
          <strong class="metric-card__value">{{ currentSession()?.sales_count ?? 0 }}</strong>
          <span class="metric-card__hint">Transacciones ligadas a la sesión actual.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Balance estimado</span>
          <strong class="metric-card__value">
            {{ formatCurrency(currentSession()?.cash_balance ?? 0) }}
          </strong>
          <span class="metric-card__hint">Apertura mas ingresos de efectivo registrados.</span>
        </article>
      </section>

      <section class="cash-layout">
        <mat-card appearance="outlined" class="cash-card">
          <mat-card-header>
            <mat-card-title>
              @if (currentSession()) {
                Cerrar caja
              } @else {
                Abrir caja
              }
            </mat-card-title>
            <mat-card-subtitle>
              @if (currentSession()) {
                {{ currentSession()?.register?.name || 'Caja actual' }}
              } @else {
                Selecciona la caja y registra el monto inicial.
              }
            </mat-card-subtitle>
          </mat-card-header>

          <mat-card-content class="stack">
            @if (currentSession(); as session) {
              <div class="cash-summary">
                <mat-chip-set>
                  <mat-chip>Apertura {{ formatCurrency(session.opening_amount) }}</mat-chip>
                  <mat-chip>Ventas {{ formatCurrency(session.sales_total) }}</mat-chip>
                  <mat-chip highlighted>Balance {{ formatCurrency(session.cash_balance) }}</mat-chip>
                </mat-chip-set>

                <p class="muted">
                  Abierta el {{ formatDateTime(session.opened_at) }} por
                  {{ session.opened_by?.name || 'usuario actual' }}.
                </p>
              </div>

              <form class="cash-form" [formGroup]="closeForm" (ngSubmit)="closeSession()">
                <mat-form-field appearance="outline">
                  <mat-label>Monto de cierre</mat-label>
                  <input matInput type="number" min="0" step="0.01" formControlName="closing_amount" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Notas de cierre</mat-label>
                  <textarea matInput rows="3" formControlName="notes"></textarea>
                </mat-form-field>

                <div class="cta-row">
                  <button mat-flat-button color="primary" type="submit" [disabled]="saving()">
                    Cerrar caja
                  </button>
                </div>
              </form>
            } @else {
              <form class="cash-form" [formGroup]="openForm" (ngSubmit)="openSession()">
                <mat-form-field appearance="outline">
                  <mat-label>Caja</mat-label>
                  <mat-select formControlName="cash_register_id">
                    @for (register of registers(); track register.id) {
                      <mat-option [value]="register.id">
                        {{ register.name }}
                        @if (register.location) {
                          · {{ register.location }}
                        }
                      </mat-option>
                    }
                  </mat-select>
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Monto de apertura</mat-label>
                  <input matInput type="number" min="0" step="0.01" formControlName="opening_amount" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Notas</mat-label>
                  <textarea matInput rows="3" formControlName="notes"></textarea>
                </mat-form-field>

                <div class="cta-row">
                  <button mat-flat-button color="primary" type="submit" [disabled]="saving()">
                    Abrir caja
                  </button>
                </div>
              </form>
            }
          </mat-card-content>
        </mat-card>

        <mat-card appearance="outlined" class="cash-card">
          <mat-card-header>
            <mat-card-title>Sesiones recientes</mat-card-title>
            <mat-card-subtitle>Historial operativo para trazabilidad de caja.</mat-card-subtitle>
          </mat-card-header>

          <mat-card-content>
            @if (sessions().length === 0) {
              <p class="muted">Todavia no hay sesiones registradas.</p>
            } @else {
              <mat-list>
                @for (session of sessions(); track session.id) {
                  <mat-list-item class="cash-history-item">
                    <div class="cash-history-item__content">
                      <strong>{{ session.register?.name || 'Caja' }}</strong>
                      <span>
                        {{ session.opened_by?.name || 'Sin usuario' }} ·
                        {{ session.status === 'open' ? 'Abierta' : 'Cerrada' }}
                      </span>
                      <small>
                        Apertura {{ formatCurrency(session.opening_amount) }}
                        · Ventas {{ formatCurrency(session.sales_total) }}
                      </small>
                    </div>
                    <div class="cash-history-item__meta">
                      <mat-chip-set>
                        <mat-chip>{{ formatDateTime(session.opened_at) }}</mat-chip>
                      </mat-chip-set>
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
    .cash-page {
      align-content: start;
    }

    .cash-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
    }

    .cash-card {
      border-radius: 1.5rem;
      height: 100%;
    }

    .cash-card mat-card-content:first-of-type {
      margin-top: 1rem;
    }

    .cash-form,
    .cash-summary {
      display: grid;
      gap: 0.9rem;
    }

    .cash-history-item {
      height: auto;
      align-items: start;
      padding-block: 0.5rem;
    }

    .cash-history-item__content {
      display: grid;
      gap: 0.25rem;
      min-width: 0;
      padding-right: 1rem;
    }

    .cash-history-item__content span,
    .cash-history-item__content small {
      color: var(--text-muted);
    }

    .cash-history-item__meta {
      white-space: nowrap;
    }

    @media (max-width: 960px) {
      .cash-layout {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class CashPageComponent {
  private readonly api = inject(CashApiService);
  private readonly fb = inject(FormBuilder);

  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly registers = signal<CashRegister[]>([]);
  protected readonly sessions = signal<CashSession[]>([]);
  protected readonly currentSession = signal<CashSession | null>(null);

  protected readonly openForm = this.fb.group({
    cash_register_id: ['', [Validators.required]],
    opening_amount: [0, [Validators.required, Validators.min(0)]],
    notes: [''],
  });

  protected readonly closeForm = this.fb.group({
    closing_amount: [0, [Validators.required, Validators.min(0)]],
    notes: [''],
  });

  private readonly currencyFormatter = new Intl.NumberFormat('es-EC', {
    style: 'currency',
    currency: 'USD',
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
      await this.loadRegisters();
      await Promise.all([this.loadSessions(), this.loadCurrentSession()]);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected async openSession(): Promise<void> {
    if (this.openForm.invalid) {
      this.openForm.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    try {
      const raw = this.openForm.getRawValue();

      await this.api.openSession({
        cash_register_id: Number(raw.cash_register_id),
        opening_amount: Number(raw.opening_amount),
        notes: this.nullableText(raw.notes),
      });

      await Promise.all([this.loadCurrentSession(), this.loadSessions()]);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected async closeSession(): Promise<void> {
    const session = this.currentSession();

    if (!session) {
      return;
    }

    if (this.closeForm.invalid) {
      this.closeForm.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    try {
      const raw = this.closeForm.getRawValue();

      await this.api.closeSession(session.id, {
        closing_amount: Number(raw.closing_amount),
        notes: this.nullableText(raw.notes),
      });

      await Promise.all([this.loadCurrentSession(), this.loadSessions()]);
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected formatCurrency(value: number): string {
    return this.currencyFormatter.format(value);
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return 'Sin fecha';
    }

    return this.dateTimeFormatter.format(new Date(value));
  }

  private async loadRegisters(): Promise<void> {
    this.registers.set(await this.api.listRegisters());
  }

  private async loadSessions(): Promise<void> {
    this.sessions.set(await this.api.listSessions());
  }

  private async loadCurrentSession(): Promise<void> {
    const session = await this.api.getCurrentSession();
    this.currentSession.set(session);

    if (session) {
      this.closeForm.patchValue({
        closing_amount: session.cash_balance,
        notes: session.notes ?? '',
      });
    } else {
      const fallbackRegister = this.registers()[0];

      this.openForm.patchValue({
        cash_register_id: fallbackRegister ? String(fallbackRegister.id) : '',
        opening_amount: 0,
        notes: '',
      });
      this.closeForm.reset({
        closing_amount: 0,
        notes: '',
      });
    }
  }

  private nullableText(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }
}
