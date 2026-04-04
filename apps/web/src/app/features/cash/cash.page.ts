import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { MatProgressBarModule } from '@angular/material/progress-bar';
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
  imports: [ReactiveFormsModule, MatProgressBarModule],
  template: `
    <section class="stack cash-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Caja</span>
          <h1 class="page-title">Control diario de apertura, balance y movimientos.</h1>
          <p class="page-description">
            La vista principal queda enfocada en estado actual, historial reciente y acciones
            puntuales. Las operaciones sensibles se registran en flujos separados.
          </p>
        </div>
        <span class="pill">Operacion diaria</span>
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

      <article class="surface stack cash-control">
        <div class="cash-control__header">
          <div class="page-header__copy">
            <span class="page-kicker">Operacion actual</span>
            <h2 class="cash-section__title">
              @if (currentSession()) {
                Caja activa
              } @else {
                Caja pendiente de apertura
              }
            </h2>
            <p class="page-description">
              @if (currentSession(); as session) {
                {{ session.register?.name || 'Caja actual' }} abierta el
                {{ formatDateTime(session.opened_at) }} por
                {{ session.opened_by?.name || 'usuario actual' }}.
              } @else {
                Abre una caja antes de registrar entradas, gastos o cierres del turno.
              }
            </p>
          </div>

          <div class="cta-row">
            <button class="btn btn--ghost" type="button" (click)="load()">Refrescar</button>
            @if (currentSession()) {
              <button class="btn" type="button" (click)="openMovementDialog()">Nuevo movimiento</button>
              <button class="btn btn--primary" type="button" (click)="openSessionDialog()">
                Cerrar caja
              </button>
            } @else {
              <button class="btn btn--primary" type="button" (click)="openSessionDialog()">
                Abrir caja
              </button>
            }
          </div>
        </div>

        @if (currentSession(); as session) {
          <div class="cash-state-grid">
            <article class="cash-state-card">
              <span class="metric-card__label">Caja</span>
              <strong>{{ session.register?.name || 'Caja actual' }}</strong>
              <small>{{ session.register?.location || 'Sin ubicacion registrada' }}</small>
            </article>

            <article class="cash-state-card">
              <span class="metric-card__label">Apertura</span>
              <strong>{{ formatCurrency(session.opening_amount) }}</strong>
              <small>{{ formatDateTime(session.opened_at) }}</small>
            </article>

            <article class="cash-state-card">
              <span class="metric-card__label">Movimientos</span>
              <strong>{{ currentSessionMovements().length }}</strong>
              <small>{{ formatCurrency(manualIncomeTotal()) }} en entradas manuales</small>
            </article>

            <article class="cash-state-card">
              <span class="metric-card__label">Balance</span>
              <strong>{{ formatCurrency(session.cash_balance) }}</strong>
              <small>{{ formatCurrency(manualExpenseTotal()) }} en gastos manuales</small>
            </article>
          </div>
        } @else {
          <article class="cash-empty-state">
            <strong>No hay una caja operando en este momento.</strong>
            <p class="muted">
              Mantuvimos la pantalla principal limpia para consulta. La apertura y el cierre ahora
              viven en un flujo separado.
            </p>
          </article>
        }
      </article>

      <section class="cash-layout">
        <article class="surface stack">
          <div class="cash-block__header">
            <div>
              <span class="page-kicker">Movimientos manuales</span>
              <h2 class="cash-section__title">Historial reciente</h2>
            </div>

            @if (currentSession()) {
              <button class="btn btn--ghost" type="button" (click)="openMovementDialog()">
                Registrar
              </button>
            }
          </div>

          @if (displayedMovements().length === 0) {
            <p class="muted">
              @if (currentSession()) {
                Esta caja todavia no tiene entradas o salidas manuales registradas.
              } @else {
                Aun no hay movimientos manuales disponibles para mostrar.
              }
            </p>
          } @else {
            <div class="cash-list">
              @for (movement of displayedMovements(); track movement.id) {
                <article class="cash-item">
                  <div class="cash-item__copy">
                    <div class="cash-item__head">
                      <strong>{{ movement.notes || labelForMovementCategory(movement.category) }}</strong>
                      <div class="badge-row">
                        <span
                          class="pill"
                          [class.cash-pill-income]="movement.type === 'income'"
                          [class.cash-pill-expense]="movement.type === 'expense'"
                        >
                          {{ labelForMovementType(movement.type) }}
                        </span>
                        <span class="pill pill--muted">
                          {{ labelForMovementCategory(movement.category) }}
                        </span>
                      </div>
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
                        ·
                        {{ movement.cash_session?.status === 'open' ? 'Sesion abierta' : 'Sesion cerrada' }}
                      }
                    </small>
                  </div>

                  <div class="cash-item__meta">
                    <strong
                      class="cash-item__amount"
                      [class.cash-item__amount--income]="movement.type === 'income'"
                      [class.cash-item__amount--expense]="movement.type === 'expense'"
                    >
                      {{ formatSignedAmount(movement) }}
                    </strong>

                    @if (movement.can_manage) {
                      <div class="cta-row">
                        <button class="btn btn--ghost" type="button" (click)="editMovement(movement)">
                          Editar
                        </button>
                        <button class="btn" type="button" (click)="deleteMovement(movement)" [disabled]="saving()">
                          Eliminar
                        </button>
                      </div>
                    }
                  </div>
                </article>
              }
            </div>
          }
        </article>

        <article class="surface stack">
          <div class="cash-block__header">
            <div>
              <span class="page-kicker">Sesiones</span>
              <h2 class="cash-section__title">Actividad reciente</h2>
            </div>
          </div>

          @if (recentSessions().length === 0) {
            <p class="muted">Todavia no hay sesiones registradas.</p>
          } @else {
            <div class="cash-session-list">
              @for (session of recentSessions(); track session.id) {
                <article class="cash-session-item">
                  <div class="cash-session-item__copy">
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

                  <div class="badge-row">
                    <span class="pill pill--muted">{{ formatDateTime(session.opened_at) }}</span>
                  </div>
                </article>
              }
            </div>
          }
        </article>
      </section>

      @if (sessionDialogOpen()) {
        <div class="cash-modal-backdrop" (click)="closeSessionDialog()"></div>
        <section class="cash-modal" role="dialog" aria-modal="true">
          <article class="surface cash-modal__panel">
            <header class="cash-modal__header">
              <div class="page-header__copy">
                <span class="page-kicker">Caja</span>
                <h2 class="page-title">
                  @if (currentSession()) {
                    Cerrar caja
                  } @else {
                    Abrir caja
                  }
                </h2>
                <p class="page-description">
                  @if (currentSession()) {
                    Confirma el monto final y deja observaciones del turno.
                  } @else {
                    Selecciona la caja y registra la base inicial del turno.
                  }
                </p>
              </div>
              <button class="btn btn--ghost" type="button" (click)="closeSessionDialog()">Cerrar</button>
            </header>

            @if (currentSession()) {
              <form class="form-grid" [formGroup]="closeForm" (ngSubmit)="closeSession()">
                <div class="split">
                  <div class="field">
                    <label for="cash-closing-amount">Monto de cierre</label>
                    <input
                      id="cash-closing-amount"
                      type="number"
                      min="0"
                      step="0.01"
                      formControlName="closing_amount"
                    />
                  </div>

                  <div class="field">
                    <label for="cash-current-balance">Balance estimado</label>
                    <input
                      id="cash-current-balance"
                      type="text"
                      [value]="formatCurrency(currentSession()?.cash_balance ?? 0)"
                      readonly
                    />
                  </div>
                </div>

                <div class="field">
                  <label for="cash-close-notes">Notas de cierre</label>
                  <textarea id="cash-close-notes" formControlName="notes"></textarea>
                </div>

                <div class="cta-row cash-modal__actions">
                  <button class="btn btn--ghost" type="button" (click)="closeSessionDialog()">
                    Cancelar
                  </button>
                  <button class="btn btn--primary" type="submit" [disabled]="saving()">
                    {{ saving() ? 'Guardando...' : 'Cerrar caja' }}
                  </button>
                </div>
              </form>
            } @else {
              <form class="form-grid" [formGroup]="openForm" (ngSubmit)="openSession()">
                <div class="split">
                  <div class="field">
                    <label for="cash-register">Caja</label>
                    <select id="cash-register" formControlName="cash_register_id">
                      <option value="">Selecciona una caja</option>
                      @for (register of registers(); track register.id) {
                        <option [value]="register.id">
                          {{ register.name }}
                          @if (register.location) {
                            · {{ register.location }}
                          }
                        </option>
                      }
                    </select>
                  </div>

                  <div class="field">
                    <label for="cash-opening-amount">Monto de apertura</label>
                    <input
                      id="cash-opening-amount"
                      type="number"
                      min="0"
                      step="0.01"
                      formControlName="opening_amount"
                    />
                  </div>
                </div>

                <div class="field">
                  <label for="cash-open-notes">Notas</label>
                  <textarea id="cash-open-notes" formControlName="notes"></textarea>
                </div>

                <div class="cta-row cash-modal__actions">
                  <button class="btn btn--ghost" type="button" (click)="closeSessionDialog()">
                    Cancelar
                  </button>
                  <button class="btn btn--primary" type="submit" [disabled]="saving()">
                    {{ saving() ? 'Guardando...' : 'Abrir caja' }}
                  </button>
                </div>
              </form>
            }
          </article>
        </section>
      }

      @if (movementDialogOpen()) {
        <div class="cash-modal-backdrop" (click)="closeMovementDialog()"></div>
        <section class="cash-modal" role="dialog" aria-modal="true">
          <article class="surface cash-modal__panel">
            <header class="cash-modal__header">
              <div class="page-header__copy">
                <span class="page-kicker">Movimiento manual</span>
                <h2 class="page-title">
                  {{ editingMovementId() ? 'Editar movimiento' : 'Registrar movimiento' }}
                </h2>
                <p class="page-description">
                  Separa ingresos y gastos del historial principal para mantener la vista despejada.
                </p>
              </div>
              <button class="btn btn--ghost" type="button" (click)="closeMovementDialog()">Cerrar</button>
            </header>

            <form class="form-grid" [formGroup]="movementForm" (ngSubmit)="saveMovement()">
              <div class="split">
                <div class="field">
                  <label for="movement-type">Tipo</label>
                  <select
                    id="movement-type"
                    formControlName="type"
                    #movementTypeInput
                    (change)="onMovementTypeChange(movementTypeInput.value)"
                  >
                    @for (option of movementTypeOptions; track option.value) {
                      <option [value]="option.value">{{ option.label }}</option>
                    }
                  </select>
                </div>

                <div class="field">
                  <label for="movement-category">Categoria</label>
                  <select id="movement-category" formControlName="category">
                    @for (option of currentCategoryOptions(); track option.value) {
                      <option [value]="option.value">{{ option.label }}</option>
                    }
                  </select>
                </div>
              </div>

              <div class="split">
                <div class="field">
                  <label for="movement-amount">Monto</label>
                  <input
                    id="movement-amount"
                    type="number"
                    min="0.01"
                    step="0.01"
                    formControlName="amount"
                  />
                </div>

                <div class="field">
                  <label for="movement-occurred-at">Fecha y hora</label>
                  <input id="movement-occurred-at" type="datetime-local" formControlName="occurred_at" />
                </div>
              </div>

              <div class="field">
                <label for="movement-notes">Detalle</label>
                <textarea id="movement-notes" formControlName="notes"></textarea>
              </div>

              <div class="cta-row cash-modal__actions">
                @if (editingMovementId()) {
                  <button class="btn btn--ghost" type="button" (click)="cancelMovementEdit()">
                    Cancelar edicion
                  </button>
                } @else {
                  <button class="btn btn--ghost" type="button" (click)="closeMovementDialog()">
                    Cancelar
                  </button>
                }

                <button class="btn btn--primary" type="submit" [disabled]="saving()">
                  @if (saving()) {
                    Guardando...
                  } @else if (editingMovementId()) {
                    Actualizar movimiento
                  } @else {
                    Registrar movimiento
                  }
                </button>
              </div>
            </form>
          </article>
        </section>
      }
    </section>
  `,
  styles: `
    .cash-page {
      align-content: start;
    }

    .cash-control {
      gap: 1.25rem;
    }

    .cash-control__header,
    .cash-block__header,
    .cash-modal__header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .cash-section__title {
      margin: 0.2rem 0 0;
      color: var(--text-soft);
      font-size: 1.35rem;
      line-height: 1.15;
      letter-spacing: -0.03em;
    }

    .cash-state-grid {
      display: grid;
      gap: 0.9rem;
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .cash-state-card,
    .cash-empty-state {
      display: grid;
      gap: 0.35rem;
      border: 1px solid rgba(148, 163, 184, 0.18);
      border-radius: 1.35rem;
      background: rgba(246, 249, 252, 0.78);
      padding: 1rem;
    }

    .cash-state-card strong {
      font-size: 1.05rem;
      color: var(--text-soft);
    }

    .cash-state-card small {
      color: var(--text-muted);
    }

    .cash-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: minmax(0, 1.1fr) minmax(20rem, 0.9fr);
    }

    .cash-list,
    .cash-session-list {
      display: grid;
      gap: 0.85rem;
    }

    .cash-success {
      border-color: rgba(19, 128, 77, 0.18);
      background: rgba(19, 128, 77, 0.08);
    }

    .cash-item,
    .cash-session-item {
      display: grid;
      gap: 0.75rem;
      border: 1px solid rgba(148, 163, 184, 0.18);
      border-radius: 1.25rem;
      background: rgba(246, 249, 252, 0.88);
      padding: 1rem;
    }

    .cash-item {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .cash-item__copy,
    .cash-session-item__copy {
      display: grid;
      gap: 0.25rem;
      min-width: 0;
    }

    .cash-item__copy span,
    .cash-item__copy small,
    .cash-session-item__copy span,
    .cash-session-item__copy small {
      color: var(--text-muted);
    }

    .cash-item__head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .cash-item__meta {
      display: grid;
      gap: 0.7rem;
      justify-items: end;
    }

    .cash-item__amount {
      font-size: 1rem;
      font-weight: 800;
      white-space: nowrap;
    }

    .cash-item__amount--income,
    .cash-pill-income {
      background: rgba(19, 128, 77, 0.12);
      color: #166534;
    }

    .cash-item__amount--expense,
    .cash-pill-expense {
      background: rgba(148, 28, 28, 0.12);
      color: #991b1b;
    }

    .cash-item__amount--income,
    .cash-item__amount--expense {
      background: transparent;
    }

    .cash-modal-backdrop {
      position: fixed;
      inset: 0;
      z-index: 60;
      background: rgba(9, 14, 25, 0.42);
    }

    .cash-modal {
      position: fixed;
      inset: 0;
      z-index: 61;
      display: grid;
      place-items: center;
      padding: 1.25rem;
    }

    .cash-modal__panel {
      width: min(100%, 44rem);
      max-height: calc(100vh - 2.5rem);
      overflow: auto;
    }

    .cash-modal__actions {
      justify-content: flex-end;
    }

    @media (max-width: 960px) {
      .cash-layout,
      .cash-state-grid {
        grid-template-columns: 1fr;
      }

      .cash-item__meta {
        justify-items: start;
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
  protected readonly sessionDialogOpen = signal(false);
  protected readonly movementDialogOpen = signal(false);
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

  protected readonly displayedMovements = computed(() => {
    const items = this.currentSession() ? this.currentSessionMovements() : this.movements();
    return items.slice(0, 8);
  });

  protected readonly recentSessions = computed(() => this.sessions().slice(0, 6));

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

      this.sessionDialogOpen.set(false);
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

      this.sessionDialogOpen.set(false);
      this.successMessage.set('La caja fue cerrada correctamente.');
      await this.reloadCashContext();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected onMovementTypeChange(value: CashMovementType | string): void {
    const normalizedValue: CashMovementType = value === 'income' ? 'income' : 'expense';

    const nextCategory =
      normalizedValue === 'income'
        ? this.incomeCategoryOptions[0]?.value ?? 'other_income'
        : this.expenseCategoryOptions[0]?.value ?? 'operational_expense';

    this.movementForm.patchValue({
      type: normalizedValue,
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

      this.movementDialogOpen.set(false);
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
    this.movementDialogOpen.set(true);
    this.movementForm.patchValue({
      type: movement.type,
      category: movement.category,
      amount: movement.amount,
      occurred_at: this.toDateTimeLocalInput(movement.occurred_at),
      notes: movement.notes ?? '',
    });
  }

  protected cancelMovementEdit(): void {
    this.movementDialogOpen.set(false);
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
        this.movementDialogOpen.set(false);
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

  protected openSessionDialog(): void {
    this.sessionDialogOpen.set(true);
  }

  protected closeSessionDialog(): void {
    this.sessionDialogOpen.set(false);
  }

  protected openMovementDialog(): void {
    if (!this.currentSession()) {
      return;
    }

    this.movementDialogOpen.set(true);
  }

  protected closeMovementDialog(): void {
    this.movementDialogOpen.set(false);
    this.resetMovementForm(this.currentSession());
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
      this.movementDialogOpen.set(false);
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

    if (this.currentSession()) {
      this.movementDialogOpen.set(true);
    }
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
