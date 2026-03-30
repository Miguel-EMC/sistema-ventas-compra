import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
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
import { CashApiService } from './cash.api';
import {
  CashMovement,
  CashMovementPayload,
  CashMovementType,
  CashRegister,
  CashSession,
} from './cash.types';

@Component({
  selector: 'app-cash-page',
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
    <section class="stack cash-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Caja</span>
          <h1 class="page-title">Apertura, cierre y movimientos manuales de caja.</h1>
          <p class="page-description">
            Este modulo ya cubre la operación diaria del POS nuevo: abrir caja, registrar gastos o
            entradas manuales y cerrar la sesión con trazabilidad completa.
          </p>
        </div>
        <span class="pill">Caja + gastos + ingresos manuales</span>
      </header>

      @if (legacyNotice()) {
        <article class="surface surface--muted stack">
          <span class="page-kicker">Migracion</span>
          <strong>{{ legacyNotice() }}</strong>
        </article>
      }

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
        <article class="surface stack cash-success">
          <span class="page-kicker">Caja</span>
          <strong>{{ successMessage() }}</strong>
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
          <span class="metric-card__label">Entradas manuales</span>
          <strong class="metric-card__value">{{ formatCurrency(manualIncomeTotal()) }}</strong>
          <span class="metric-card__hint">Movimientos de entrada registrados en la sesión activa.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Gastos manuales</span>
          <strong class="metric-card__value">{{ formatCurrency(manualExpenseTotal()) }}</strong>
          <span class="metric-card__hint">Salidas manuales cargadas desde la nueva plataforma.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Balance estimado</span>
          <strong class="metric-card__value">
            {{ formatCurrency(currentSession()?.cash_balance ?? 0) }}
          </strong>
          <span class="metric-card__hint">Apertura mas ingresos menos egresos y ventas en efectivo.</span>
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
                  <mat-chip>Entradas {{ formatCurrency(manualIncomeTotal()) }}</mat-chip>
                  <mat-chip>Gastos {{ formatCurrency(manualExpenseTotal()) }}</mat-chip>
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
            <mat-card-title>Movimientos manuales</mat-card-title>
            <mat-card-subtitle>Equivalente nuevo de entradas y salidas del legacy.</mat-card-subtitle>
          </mat-card-header>

          <mat-card-content class="stack">
            @if (currentSession(); as session) {
              <mat-chip-set>
                <mat-chip>{{ session.register?.name || 'Caja actual' }}</mat-chip>
                <mat-chip>{{ currentSessionMovements().length }} movimiento(s)</mat-chip>
                <mat-chip highlighted>{{ formatCurrency(session.cash_balance) }}</mat-chip>
              </mat-chip-set>

              <form class="cash-form" [formGroup]="movementForm" (ngSubmit)="saveMovement()">
                <div class="cash-form-grid">
                  <mat-form-field appearance="outline">
                    <mat-label>Tipo</mat-label>
                    <mat-select
                      formControlName="type"
                      (selectionChange)="onMovementTypeChange($event.value)"
                    >
                      @for (option of movementTypeOptions; track option.value) {
                        <mat-option [value]="option.value">{{ option.label }}</mat-option>
                      }
                    </mat-select>
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Categoria</mat-label>
                    <mat-select formControlName="category">
                      @for (option of currentCategoryOptions(); track option.value) {
                        <mat-option [value]="option.value">{{ option.label }}</mat-option>
                      }
                    </mat-select>
                  </mat-form-field>
                </div>

                <div class="cash-form-grid">
                  <mat-form-field appearance="outline">
                    <mat-label>Monto</mat-label>
                    <input matInput type="number" min="0.01" step="0.01" formControlName="amount" />
                  </mat-form-field>

                  <mat-form-field appearance="outline">
                    <mat-label>Fecha y hora</mat-label>
                    <input matInput type="datetime-local" formControlName="occurred_at" />
                  </mat-form-field>
                </div>

                <mat-form-field appearance="outline">
                  <mat-label>Detalle</mat-label>
                  <textarea matInput rows="3" formControlName="notes"></textarea>
                </mat-form-field>

                <div class="cta-row">
                  @if (editingMovementId()) {
                    <button mat-stroked-button type="button" (click)="cancelMovementEdit()" [disabled]="saving()">
                      Cancelar edicion
                    </button>
                  }
                  <button mat-flat-button color="primary" type="submit" [disabled]="saving()">
                    @if (editingMovementId()) {
                      Actualizar movimiento
                    } @else {
                      Registrar movimiento
                    }
                  </button>
                </div>
              </form>
            } @else {
              <p class="muted">
                Abre una caja para registrar gastos o entradas manuales y reemplazar el modulo legacy de cuentas.
              </p>
            }

            <mat-divider></mat-divider>

            <div class="stack">
              <span class="page-kicker">Historial manual</span>

              @if (movements().length === 0) {
                <p class="muted">Todavia no hay movimientos manuales registrados.</p>
              } @else {
                <div class="cash-movement-list">
                  @for (movement of movements(); track movement.id) {
                    <article class="cash-movement-item">
                      <div class="cash-history-item__content">
                        <div class="cash-movement-item__head">
                          <strong>{{ movement.notes || labelForMovementCategory(movement.category) }}</strong>
                          <mat-chip-set>
                            <mat-chip
                              [class.cash-chip-income]="movement.type === 'income'"
                              [class.cash-chip-expense]="movement.type === 'expense'"
                            >
                              {{ labelForMovementType(movement.type) }}
                            </mat-chip>
                            <mat-chip>{{ labelForMovementCategory(movement.category) }}</mat-chip>
                          </mat-chip-set>
                        </div>
                        <span>
                          {{ formatDateTime(movement.occurred_at) }}
                          @if (movement.user?.name) {
                            · {{ movement.user?.name }}
                          }
                        </span>
                        <small>
                          {{ movement.cash_session?.register_name || 'Caja sin nombre' }}
                          @if (movement.cash_session?.status) {
                            · {{ movement.cash_session?.status === 'open' ? 'Sesion abierta' : 'Sesion cerrada' }}
                          }
                        </small>
                      </div>

                      <div class="cash-history-item__meta">
                        <strong
                          [class.cash-amount-income]="movement.type === 'income'"
                          [class.cash-amount-expense]="movement.type === 'expense'"
                        >
                          {{ formatSignedAmount(movement) }}
                        </strong>

                        @if (movement.can_manage) {
                          <div class="cta-row">
                            <button mat-stroked-button type="button" (click)="editMovement(movement)" [disabled]="saving()">
                              Editar
                            </button>
                            <button mat-stroked-button type="button" (click)="deleteMovement(movement)" [disabled]="saving()">
                              Eliminar
                            </button>
                          </div>
                        }
                      </div>
                    </article>
                  }
                </div>
              }
            </div>
          </mat-card-content>
        </mat-card>
      </section>

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
                      · Egresos {{ formatCurrency(session.cash_out_total) }}
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
  `,
  styles: `
    .cash-page {
      align-content: start;
    }

    .cash-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(0, 0.92fr) minmax(0, 1.08fr);
    }

    .cash-card {
      border-radius: 1.5rem;
    }

    .cash-card mat-card-content:first-of-type {
      margin-top: 1rem;
    }

    .cash-success {
      border-color: rgba(19, 128, 77, 0.18);
      background: rgba(19, 128, 77, 0.08);
    }

    .cash-form,
    .cash-summary,
    .cash-movement-list {
      display: grid;
      gap: 0.9rem;
    }

    .cash-form-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(2, minmax(0, 1fr));
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
      display: grid;
      gap: 0.5rem;
      justify-items: end;
      white-space: nowrap;
    }

    .cash-movement-item {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: rgba(15, 76, 129, 0.03);
      padding: 1rem;
    }

    .cash-movement-item__head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .cash-chip-income {
      background: rgba(19, 128, 77, 0.14);
      color: #166534;
    }

    .cash-chip-expense {
      background: rgba(148, 28, 28, 0.14);
      color: #991b1b;
    }

    .cash-amount-income {
      color: #166534;
    }

    .cash-amount-expense {
      color: #991b1b;
    }

    @media (max-width: 960px) {
      .cash-layout,
      .cash-form-grid {
        grid-template-columns: 1fr;
      }

      .cash-history-item__meta {
        justify-items: start;
        white-space: normal;
      }
    }
  `,
})
export class CashPageComponent {
  private readonly api = inject(CashApiService);
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private legacyPrefillApplied = false;

  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly editingMovementId = signal<number | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);
  protected readonly registers = signal<CashRegister[]>([]);
  protected readonly sessions = signal<CashSession[]>([]);
  protected readonly currentSession = signal<CashSession | null>(null);
  protected readonly movements = signal<CashMovement[]>([]);

  protected readonly openForm = this.fb.group({
    cash_register_id: ['', [Validators.required]],
    opening_amount: [0, [Validators.required, Validators.min(0)]],
    notes: [''],
  });

  protected readonly closeForm = this.fb.group({
    closing_amount: [0, [Validators.required, Validators.min(0)]],
    notes: [''],
  });

  protected readonly movementForm = this.fb.group({
    type: ['expense' as CashMovementType, [Validators.required]],
    category: ['operational_expense', [Validators.required]],
    amount: [0, [Validators.required, Validators.min(0.01)]],
    occurred_at: [this.nowDateTimeLocalInput(), [Validators.required]],
    notes: [''],
  });

  protected readonly currentSessionMovements = computed(() => {
    const session = this.currentSession();

    if (!session) {
      return [];
    }

    return this.movements().filter((movement) => movement.cash_session?.id === session.id);
  });

  protected readonly manualIncomeTotal = computed(() =>
    this.currentSessionMovements()
      .filter((movement) => movement.type === 'income')
      .reduce((total, movement) => total + movement.amount, 0),
  );

  protected readonly manualExpenseTotal = computed(() =>
    this.currentSessionMovements()
      .filter((movement) => movement.type === 'expense')
      .reduce((total, movement) => total + movement.amount, 0),
  );

  private readonly currencyFormatter = new Intl.NumberFormat('es-EC', {
    style: 'currency',
    currency: 'USD',
  });

  private readonly dateTimeFormatter = new Intl.DateTimeFormat('es-EC', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });

  protected readonly movementTypeOptions: Array<{ value: CashMovementType; label: string }> = [
    { value: 'expense', label: 'Salida' },
    { value: 'income', label: 'Entrada' },
  ];

  private readonly expenseCategoryOptions = [
    { value: 'operational_expense', label: 'Gasto operativo' },
    { value: 'services', label: 'Servicios' },
    { value: 'transport', label: 'Transporte' },
    { value: 'supplies', label: 'Insumos' },
    { value: 'rent', label: 'Arriendo' },
    { value: 'misc', label: 'Varios' },
  ];

  private readonly incomeCategoryOptions = [
    { value: 'other_income', label: 'Otro ingreso' },
    { value: 'capital_injection', label: 'Ingreso de capital' },
    { value: 'loan_repayment', label: 'Recuperacion de prestamo' },
    { value: 'misc_income', label: 'Ingreso varios' },
  ];

  public constructor() {
    this.legacyNotice.set(this.resolveLegacyNotice(this.route.snapshot.queryParamMap.get('legacy')));
    void this.load();
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    try {
      await this.loadRegisters();
      await Promise.all([this.loadSessions(), this.loadCurrentSession(), this.loadMovements()]);
      this.applyLegacyPrefillFromQuery();
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
    this.successMessage.set(null);

    try {
      const raw = this.openForm.getRawValue();

      await this.api.openSession({
        cash_register_id: Number(raw.cash_register_id),
        opening_amount: Number(raw.opening_amount),
        notes: this.nullableText(raw.notes),
      });

      this.successMessage.set('La caja fue abierta correctamente.');
      await this.reloadCashContext();
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
    this.successMessage.set(null);

    try {
      const raw = this.closeForm.getRawValue();

      await this.api.closeSession(session.id, {
        closing_amount: Number(raw.closing_amount),
        notes: this.nullableText(raw.notes),
      });

      this.successMessage.set('La caja fue cerrada correctamente.');
      await this.reloadCashContext();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected onMovementTypeChange(value: CashMovementType): void {
    const nextCategory =
      value === 'income'
        ? this.incomeCategoryOptions[0]?.value ?? 'other_income'
        : this.expenseCategoryOptions[0]?.value ?? 'operational_expense';

    this.movementForm.patchValue({
      type: value,
      category: nextCategory,
    });
  }

  protected async saveMovement(): Promise<void> {
    if (!this.currentSession()) {
      return;
    }

    if (this.movementForm.invalid) {
      this.movementForm.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      const payload = this.buildMovementPayload();

      if (this.editingMovementId()) {
        await this.api.updateMovement(this.editingMovementId()!, payload);
        this.successMessage.set('El movimiento manual fue actualizado correctamente.');
      } else {
        await this.api.createMovement(payload);
        this.successMessage.set('El movimiento manual fue registrado correctamente.');
      }

      await this.reloadCashContext();
      this.resetMovementForm(this.currentSession());
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected editMovement(movement: CashMovement): void {
    this.editingMovementId.set(movement.id);
    this.movementForm.patchValue({
      type: movement.type,
      category: movement.category,
      amount: movement.amount,
      occurred_at: this.toDateTimeLocalInput(movement.occurred_at),
      notes: movement.notes ?? '',
    });
  }

  protected cancelMovementEdit(): void {
    this.resetMovementForm(this.currentSession());
  }

  protected async deleteMovement(movement: CashMovement): Promise<void> {
    if (!movement.can_manage) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      await this.api.deleteMovement(movement.id);

      if (this.editingMovementId() === movement.id) {
        this.resetMovementForm(this.currentSession());
      }

      this.successMessage.set('El movimiento manual fue eliminado correctamente.');
      await this.reloadCashContext();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected currentCategoryOptions(): Array<{ value: string; label: string }> {
    return this.movementForm.getRawValue().type === 'income'
      ? this.incomeCategoryOptions
      : this.expenseCategoryOptions;
  }

  protected labelForMovementType(type: CashMovementType): string {
    return type === 'income' ? 'Entrada' : 'Salida';
  }

  protected labelForMovementCategory(category: string): string {
    const options = [...this.expenseCategoryOptions, ...this.incomeCategoryOptions];
    return options.find((option) => option.value === category)?.label ?? category.replaceAll('_', ' ');
  }

  protected formatCurrency(value: number): string {
    return this.currencyFormatter.format(value);
  }

  protected formatSignedAmount(movement: CashMovement): string {
    return `${movement.type === 'expense' ? '-' : '+'} ${this.formatCurrency(movement.amount)}`;
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return 'Sin fecha';
    }

    return this.dateTimeFormatter.format(new Date(value));
  }

  private async reloadCashContext(): Promise<void> {
    await Promise.all([this.loadCurrentSession(), this.loadSessions(), this.loadMovements()]);
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

      if (this.editingMovementId() === null) {
        this.resetMovementForm(session);
      }
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
      this.resetMovementForm(null);
    }
  }

  private async loadMovements(): Promise<void> {
    this.movements.set(await this.api.listMovements());
  }

  private buildMovementPayload(): CashMovementPayload {
    const raw = this.movementForm.getRawValue();

    return {
      type: (raw.type as CashMovementType) ?? 'expense',
      category: String(raw.category ?? ''),
      amount: Number(raw.amount),
      notes: this.nullableText(raw.notes),
      occurred_at: raw.occurred_at ? this.toIsoDateTime(raw.occurred_at) : new Date().toISOString(),
    };
  }

  private resetMovementForm(session: CashSession | null): void {
    this.editingMovementId.set(null);
    this.movementForm.reset({
      type: 'expense',
      category: this.expenseCategoryOptions[0]?.value ?? 'operational_expense',
      amount: 0,
      occurred_at: this.nowDateTimeLocalInput(),
      notes: '',
    });
  }

  private applyLegacyPrefillFromQuery(): void {
    if (this.legacyPrefillApplied) {
      return;
    }

    this.legacyPrefillApplied = true;

    const query = this.route.snapshot.queryParamMap;
    const type = this.normalizeMovementType(query.get('type'));
    const amount = this.normalizePositiveNumber(query.get('amount'));
    const notes = this.nullableText(query.get('notes'));
    const occurredAt = this.normalizeDateTimeLocal(query.get('occurred_at'));

    if (type === null && amount === null && notes === null && occurredAt === null) {
      return;
    }

    this.movementForm.patchValue({
      type: type ?? 'expense',
      category: type === 'income' ? 'other_income' : 'operational_expense',
      amount: amount ?? 0,
      occurred_at: occurredAt ?? this.nowDateTimeLocalInput(),
      notes: notes ?? '',
    });
  }

  private nullableText(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }

  private normalizeMovementType(value: string | null): CashMovementType | null {
    switch ((value ?? '').trim().toLowerCase()) {
      case 'income':
      case 'entrada':
      case 'e':
        return 'income';
      case 'expense':
      case 'salida':
      case 's':
        return 'expense';
      default:
        return null;
    }
  }

  private normalizePositiveNumber(value: string | null): number | null {
    if (value === null) {
      return null;
    }

    const normalized = Number(value);

    return Number.isFinite(normalized) && normalized > 0 ? normalized : null;
  }

  private normalizeDateTimeLocal(value: string | null): string | null {
    const normalized = (value ?? '').trim();

    if (normalized === '') {
      return null;
    }

    return normalized.length === 16 ? normalized : normalized.slice(0, 16);
  }

  private nowDateTimeLocalInput(): string {
    return this.toDateTimeLocalInput(new Date().toISOString());
  }

  private toDateTimeLocalInput(value: string | null): string {
    const date = value ? new Date(value) : new Date();
    const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);

    return localDate.toISOString().slice(0, 16);
  }

  private toIsoDateTime(value: string): string {
    return new Date(value).toISOString();
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch ((value ?? '').trim()) {
      case 'cash-movement-write':
        return 'El formulario legacy de cuenta ya no registra movimientos en la base anterior. Revisa los datos precargados y confirmalos aqui.';
      case 'cash-movement-delete':
        return 'La eliminacion legacy de movimientos de cuenta fue retirada. Usa esta caja nueva para corregir o depurar movimientos manuales.';
      default:
        return null;
    }
  }
}
