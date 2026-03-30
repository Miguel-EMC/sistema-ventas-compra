import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatDividerModule } from '@angular/material/divider';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSelectModule } from '@angular/material/select';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { AuthService } from '../../core/auth/auth.service';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { UsersApiService } from './users.api';
import { SaveUserPayload, UserRecord, UserRole } from './users.types';

@Component({
  selector: 'app-users-page',
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
    <section class="stack users-page">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Administracion</span>
          <h1 class="page-title">Usuarios, roles y accesos ya operan en el stack nuevo.</h1>
          <p class="page-description">
            Este modulo ya consume Laravel para altas, edicion, desactivacion y control de roles
            internos sin depender del panel legacy.
          </p>
        </div>
        <span class="pill">Angular Material + seguridad de usuarios</span>
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
        <article class="surface stack users-success">
          <span class="page-kicker">Usuarios</span>
          <strong>{{ successMessage() }}</strong>
        </article>
      }

      <section class="grid grid--cards">
        <article class="surface metric-card">
          <span class="metric-card__label">Usuarios cargados</span>
          <strong class="metric-card__value">{{ users().length }}</strong>
          <span class="metric-card__hint">Listado operativo del modulo nuevo.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Activos</span>
          <strong class="metric-card__value">{{ activeUsersCount() }}</strong>
          <span class="metric-card__hint">Usuarios habilitados para operar.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Admins activos</span>
          <strong class="metric-card__value">{{ activeAdminsCount() }}</strong>
          <span class="metric-card__hint">Proteccion base para no dejar el sistema sin admin.</span>
        </article>

        <article class="surface metric-card">
          <span class="metric-card__label">Roles</span>
          <strong class="metric-card__value">{{ roles().length }}</strong>
          <span class="metric-card__hint">Catalogo de permisos servido por Laravel.</span>
        </article>
      </section>

      <section class="users-layout">
        <mat-card appearance="outlined" class="users-card">
          <mat-card-header>
            <mat-card-title>Equipo interno</mat-card-title>
            <mat-card-subtitle>Busqueda, estado y acciones sobre usuarios migrados.</mat-card-subtitle>
          </mat-card-header>

          <mat-card-content class="stack">
            <div class="users-toolbar">
              <mat-form-field appearance="outline" class="users-toolbar__search">
                <mat-label>Buscar usuarios</mat-label>
                <input
                  matInput
                  id="user-search"
                  type="text"
                  [value]="search()"
                  (input)="onSearch($event)"
                  placeholder="Nombre, usuario o correo"
                />
              </mat-form-field>

              <div class="cta-row">
                <button mat-stroked-button type="button" (click)="load()" [disabled]="loading()">
                  Refrescar
                </button>
                <button mat-flat-button color="primary" type="button" (click)="resetForm()">
                  Nuevo usuario
                </button>
              </div>
            </div>

            <mat-divider></mat-divider>

            @if (users().length === 0) {
              <p class="muted">No hay usuarios para mostrar con los filtros actuales.</p>
            } @else {
              <div class="users-list">
                @for (user of users(); track user.id) {
                  <article class="users-list__item" [class.users-list__item--inactive]="!user.is_active">
                    <div class="users-list__main">
                      <div class="users-list__heading">
                        <strong>{{ user.display_name || user.name }}</strong>
                        @if (isCurrentUser(user)) {
                          <mat-chip>Tu sesion</mat-chip>
                        }
                      </div>

                      <span>{{ user.username }} · {{ user.email || 'sin correo' }}</span>
                      <small>
                        {{ user.role?.name || 'Sin rol' }}
                        · ultimo acceso {{ formatDateTime(user.last_login_at) }}
                      </small>

                      <mat-chip-set>
                        <mat-chip>{{ user.is_active ? 'Activo' : 'Inactivo' }}</mat-chip>
                        @if (user.role?.slug) {
                          <mat-chip>{{ user.role?.slug }}</mat-chip>
                        }
                        <mat-chip>Alta {{ formatDateTime(user.created_at) }}</mat-chip>
                      </mat-chip-set>
                    </div>

                    <div class="users-list__actions">
                      <button mat-stroked-button type="button" (click)="editUser(user)">
                        Editar
                      </button>
                      <button
                        mat-flat-button
                        color="warn"
                        type="button"
                        (click)="deleteUser(user)"
                        [disabled]="deletingUserId() === user.id || isCurrentUser(user)"
                      >
                        @if (deletingUserId() === user.id) {
                          Eliminando...
                        } @else if (isCurrentUser(user)) {
                          Sesion actual
                        } @else {
                          Eliminar
                        }
                      </button>
                    </div>
                  </article>
                }
              </div>
            }
          </mat-card-content>
        </mat-card>

        <mat-card appearance="outlined" class="users-card">
          <mat-card-header>
            <mat-card-title>
              @if (editingUserId()) {
                Editar usuario
              } @else {
                Crear usuario
              }
            </mat-card-title>
            <mat-card-subtitle>
              @if (editingUserId()) {
                Ajusta rol, estado y credenciales del perfil seleccionado.
              } @else {
                Prepara un acceso interno nuevo para la plataforma migrada.
              }
            </mat-card-subtitle>
          </mat-card-header>

          <mat-card-content class="stack">
            <form class="users-form" [formGroup]="form" (ngSubmit)="submit()">
              <div class="users-grid">
                <mat-form-field appearance="outline">
                  <mat-label>Nombre</mat-label>
                  <input matInput id="user-name" type="text" formControlName="name" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Nombre visible</mat-label>
                  <input matInput id="user-display-name" type="text" formControlName="display_name" />
                </mat-form-field>
              </div>

              <div class="users-grid">
                <mat-form-field appearance="outline">
                  <mat-label>Usuario</mat-label>
                  <input matInput id="user-username" type="text" formControlName="username" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Correo</mat-label>
                  <input matInput id="user-email" type="email" formControlName="email" />
                </mat-form-field>
              </div>

              <mat-form-field appearance="outline">
                <mat-label>Rol</mat-label>
                <mat-select id="user-role" formControlName="role_id">
                  @for (role of roles(); track role.id) {
                    <mat-option [value]="role.id">{{ role.name }}</mat-option>
                  }
                </mat-select>
              </mat-form-field>

              <div class="users-state-row">
                <mat-slide-toggle formControlName="is_active">Usuario habilitado para ingresar</mat-slide-toggle>
                <span class="muted">
                  Estado actual: {{ form.getRawValue().is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </div>

              <mat-divider></mat-divider>

              <div class="users-grid">
                <mat-form-field appearance="outline">
                  <mat-label>Password</mat-label>
                  <input matInput id="user-password" type="password" formControlName="password" />
                </mat-form-field>

                <mat-form-field appearance="outline">
                  <mat-label>Confirmar password</mat-label>
                  <input
                    matInput
                    id="user-password-confirmation"
                    type="password"
                    formControlName="password_confirmation"
                  />
                </mat-form-field>
              </div>

              <p class="muted users-note">
                @if (editingUserId()) {
                  Deja las contrasenas vacias si no quieres cambiarlas.
                } @else {
                  La contrasena inicial debe tener al menos 8 caracteres.
                }
              </p>

              <div class="cta-row">
                <button mat-stroked-button type="button" (click)="resetForm()" [disabled]="saving()">
                  Limpiar
                </button>
                <button mat-flat-button color="primary" type="submit" [disabled]="saving()">
                  @if (saving()) {
                    Guardando...
                  } @else if (editingUserId()) {
                    Actualizar usuario
                  } @else {
                    Crear usuario
                  }
                </button>
              </div>
            </form>

            @if (auth.user(); as currentUser) {
              <article class="surface surface--muted stack">
                <span class="page-kicker">Sesion actual</span>
                <strong>{{ currentUser.display_name || currentUser.name }}</strong>
                <span>{{ currentUser.role?.name || 'Sin rol' }} · {{ currentUser.username }}</span>
                <small>La API bloquea la autoeliminacion para cuidar el acceso administrativo.</small>
              </article>
            }
          </mat-card-content>
        </mat-card>
      </section>
    </section>
  `,
  styles: `
    .users-layout {
      display: grid;
      gap: 1.25rem;
      grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
      align-items: start;
    }

    .users-card mat-card-content:first-of-type {
      padding-top: 0;
    }

    .users-toolbar {
      display: flex;
      gap: 1rem;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .users-toolbar__search {
      flex: 1 1 20rem;
    }

    .users-list {
      display: grid;
      gap: 1rem;
    }

    .users-list__item {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem;
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: 1rem;
      background: rgba(255, 255, 255, 0.72);
    }

    .users-list__item--inactive {
      opacity: 0.78;
    }

    .users-list__main {
      display: grid;
      gap: 0.45rem;
    }

    .users-list__main span,
    .users-list__main small {
      color: var(--text-muted);
    }

    .users-list__heading {
      display: flex;
      gap: 0.75rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .users-list__actions {
      display: flex;
      gap: 0.75rem;
      align-items: center;
      justify-content: flex-end;
      flex-wrap: wrap;
    }

    .users-form {
      display: grid;
      gap: 1rem;
    }

    .users-state-row {
      display: flex;
      gap: 1rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .users-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .users-note {
      margin: 0;
    }

    .users-success {
      border-left: 4px solid rgba(22, 163, 74, 0.65);
    }

    @media (max-width: 1024px) {
      .users-layout {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 720px) {
      .users-grid {
        grid-template-columns: 1fr;
      }

      .users-list__item {
        flex-direction: column;
      }

      .users-list__actions {
        justify-content: flex-start;
      }
    }
  `,
})
export class UsersPageComponent {
  private readonly fb = inject(FormBuilder);
  protected readonly auth = inject(AuthService);
  private readonly usersApi = inject(UsersApiService);
  private readonly route = inject(ActivatedRoute);
  private legacyPrefillApplied = false;

  protected readonly users = signal<UserRecord[]>([]);
  protected readonly roles = signal<UserRole[]>([]);
  protected readonly search = signal('');
  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly deletingUserId = signal<number | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly editingUserId = signal<number | null>(null);
  protected readonly legacyNotice = signal<string | null>(null);
  protected readonly activeUsersCount = computed(
    () => this.users().filter((user) => user.is_active).length,
  );
  protected readonly activeAdminsCount = computed(
    () =>
      this.users().filter((user) => user.is_active && user.role?.slug === 'admin').length,
  );

  protected readonly form = this.fb.group({
    name: ['', [Validators.required]],
    display_name: [''],
    username: ['', [Validators.required]],
    email: ['', [Validators.email]],
    role_id: [null as number | null, [Validators.required]],
    is_active: [true, [Validators.required]],
    password: [''],
    password_confirmation: [''],
  });

  public constructor() {
    this.legacyNotice.set(this.resolveLegacyNotice(this.route.snapshot.queryParamMap.get('legacy')));
    void this.load();
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    try {
      const rolesPromise =
        this.roles().length === 0 ? this.usersApi.listRoles() : Promise.resolve(this.roles());
      const [roles, users] = await Promise.all([rolesPromise, this.usersApi.list(this.search())]);

      if (this.roles().length === 0) {
        this.roles.set(roles);
      }

      this.users.set(users);
      this.applyLegacyPrefillFromQuery();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected onSearch(event: Event): void {
    const target = event.target as HTMLInputElement;
    this.search.set(target.value);
    void this.load();
  }

  protected editUser(user: UserRecord): void {
    this.editingUserId.set(user.id);
    this.error.set(null);
    this.successMessage.set(null);
    this.form.patchValue({
      name: user.name,
      display_name: user.display_name ?? '',
      username: user.username,
      email: user.email ?? '',
      role_id: user.role?.id ?? null,
      is_active: user.is_active,
      password: '',
      password_confirmation: '',
    });
  }

  protected resetForm(): void {
    this.editingUserId.set(null);
    this.error.set(null);
    this.successMessage.set(null);
    this.form.reset({
      name: '',
      display_name: '',
      username: '',
      email: '',
      role_id: null,
      is_active: true,
      password: '',
      password_confirmation: '',
    });
  }

  protected async submit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const passwordError = this.validatePasswordState();

    if (passwordError !== null) {
      this.error.set(passwordError);
      return;
    }

    this.saving.set(true);
    this.error.set(null);
    this.successMessage.set(null);

    const payload = this.buildPayload();

    try {
      if (this.editingUserId()) {
        await this.usersApi.update(this.editingUserId()!, payload);
        this.successMessage.set('Usuario actualizado correctamente.');
      } else {
        await this.usersApi.create(payload);
        this.successMessage.set('Usuario creado correctamente.');
      }

      this.form.reset({
        name: '',
        display_name: '',
        username: '',
        email: '',
        role_id: null,
        is_active: true,
        password: '',
        password_confirmation: '',
      });
      this.editingUserId.set(null);
      await this.load();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected async deleteUser(user: UserRecord): Promise<void> {
    if (this.isCurrentUser(user)) {
      this.error.set('La sesion actual no puede eliminarse desde este panel.');
      return;
    }

    if (!window.confirm(`Estas seguro de eliminar a ${user.display_name || user.name}?`)) {
      return;
    }

    this.deletingUserId.set(user.id);
    this.error.set(null);
    this.successMessage.set(null);

    try {
      await this.usersApi.delete(user.id);
      this.successMessage.set('Usuario eliminado correctamente.');

      if (this.editingUserId() === user.id) {
        this.resetForm();
      }

      await this.load();
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.deletingUserId.set(null);
    }
  }

  protected isCurrentUser(user: UserRecord): boolean {
    return this.auth.user()?.id === user.id;
  }

  protected formatDateTime(value: string | null): string {
    if (!value) {
      return 'sin registro';
    }

    return new Intl.DateTimeFormat('es-EC', {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(new Date(value));
  }

  private buildPayload(): SaveUserPayload {
    const raw = this.form.getRawValue();
    const payload: SaveUserPayload = {
      name: raw.name?.trim() ?? '',
      display_name: raw.display_name?.trim() || null,
      username: raw.username?.trim() ?? '',
      email: raw.email?.trim() || null,
      role_id: Number(raw.role_id),
      is_active: Boolean(raw.is_active),
    };

    if ((raw.password ?? '').trim() !== '' || !this.editingUserId()) {
      payload.password = raw.password ?? '';
      payload.password_confirmation = raw.password_confirmation ?? '';
    }

    return payload;
  }

  private validatePasswordState(): string | null {
    const raw = this.form.getRawValue();
    const password = raw.password?.trim() ?? '';
    const passwordConfirmation = raw.password_confirmation?.trim() ?? '';

    if (!this.editingUserId() && password === '') {
      return 'Debes definir una contrasena para el nuevo usuario.';
    }

    if (password !== '' || passwordConfirmation !== '') {
      if (password.length < 8) {
        return 'La contrasena debe tener al menos 8 caracteres.';
      }

      if (password !== passwordConfirmation) {
        return 'La confirmacion de la contrasena no coincide.';
      }
    }

    return null;
  }

  private applyLegacyPrefillFromQuery(): void {
    if (this.legacyPrefillApplied) {
      return;
    }

    this.legacyPrefillApplied = true;

    const query = this.route.snapshot.queryParamMap;
    const name = this.nullableText(query.get('name'));
    const displayName = this.nullableText(query.get('display_name'));
    const username = this.nullableText(query.get('username'));
    const email = this.nullableText(query.get('email'));
    const legacyRole = this.nullableText(query.get('legacy_role'));

    if (name === null && displayName === null && username === null && email === null && legacyRole === null) {
      return;
    }

    this.resetForm();
    this.form.patchValue({
      name: name ?? '',
      display_name: displayName ?? '',
      username: username ?? '',
      email: email ?? '',
      role_id: this.matchRoleId(legacyRole),
    });
  }

  private matchRoleId(value: string | null): number | null {
    if (value === null) {
      return null;
    }

    const normalized = this.normalizeText(value);
    const match = this.roles().find(
      (role) =>
        this.normalizeText(role.slug) === normalized || this.normalizeText(role.name) === normalized,
    );

    return match?.id ?? null;
  }

  private nullableText(value: string | null | undefined): string | null {
    const normalized = value?.trim() ?? '';

    return normalized === '' ? null : normalized;
  }

  private normalizeText(value: string | null | undefined): string {
    return (value ?? '').trim().toLowerCase();
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch ((value ?? '').trim()) {
      case 'user-form-write':
        return 'El formulario legacy de usuarios fue retirado. Revisa los datos precargados y termina el alta o ajuste desde este panel nuevo.';
      case 'user-form-delete':
        return 'La eliminacion legacy de usuarios fue retirada. Usa este modulo para administrar accesos y estados.';
      default:
        return null;
    }
  }
}
