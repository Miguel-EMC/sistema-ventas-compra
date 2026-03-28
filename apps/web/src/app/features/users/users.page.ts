import { HttpClient } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { AuthUser, Role } from '../../core/auth/auth.types';
import { API_BASE_URL } from '../../core/config/api.config';

interface UsersResponse {
  data: AuthUser[];
}

interface RolesResponse {
  data: Role[];
}

@Component({
  selector: 'app-users-page',
  imports: [ReactiveFormsModule],
  template: `
    <section class="stack">
      <header class="page-header">
        <div class="page-header__copy">
          <span class="page-kicker">Administracion</span>
          <h1 class="page-title">Usuarios y roles migrados al panel nuevo.</h1>
          <p class="page-description">
            Esta pantalla ya trabaja contra la API Laravel para listar, crear, editar y eliminar
            usuarios internos.
          </p>
        </div>
        <span class="pill">Modulo real en produccion de migracion</span>
      </header>

      <section class="admin-layout">
        <article class="surface stack">
          <div class="toolbar">
            <div class="field field--search">
              <label for="user-search">Buscar usuarios</label>
              <input
                id="user-search"
                type="text"
                [value]="search()"
                (input)="onSearch($event)"
                placeholder="Nombre, usuario o correo"
              />
            </div>

            <div class="cta-row">
              <button class="btn" type="button" (click)="loadUsers()">Refrescar</button>
              <button class="btn btn--ghost" type="button" (click)="resetForm()">Nuevo</button>
            </div>
          </div>

          @if (users().length === 0) {
            <p class="muted">No hay usuarios para mostrar.</p>
          } @else {
            <div class="table-list">
              @for (user of users(); track user.id) {
                <article class="table-list__row">
                  <div class="table-list__copy">
                    <strong>{{ user.display_name || user.name }}</strong>
                    <span>{{ user.username }} · {{ user.email || 'sin correo' }}</span>
                    <small>{{ user.role?.name || 'Sin rol' }}</small>
                  </div>

                  <div class="table-list__actions">
                    <span class="pill">{{ user.is_active ? 'Activo' : 'Inactivo' }}</span>
                    <button class="btn btn--ghost" type="button" (click)="editUser(user)">
                      Editar
                    </button>
                    <button class="btn" type="button" (click)="deleteUser(user)">Eliminar</button>
                  </div>
                </article>
              }
            </div>
          }
        </article>

        <article class="surface stack">
          <div class="page-header">
            <div class="page-header__copy">
              <span class="page-kicker">Formulario</span>
              <h2 class="page-title">
                @if (editingUserId()) {
                  Editar usuario
                } @else {
                  Crear usuario
                }
              </h2>
            </div>
          </div>

          <form class="form-grid" [formGroup]="form" (ngSubmit)="submit()">
            <div class="split">
              <div class="field">
                <label for="user-name">Nombre</label>
                <input id="user-name" type="text" formControlName="name" />
              </div>
              <div class="field">
                <label for="user-display-name">Nombre visible</label>
                <input id="user-display-name" type="text" formControlName="display_name" />
              </div>
            </div>

            <div class="split">
              <div class="field">
                <label for="user-username">Usuario</label>
                <input id="user-username" type="text" formControlName="username" />
              </div>
              <div class="field">
                <label for="user-email">Correo</label>
                <input id="user-email" type="email" formControlName="email" />
              </div>
            </div>

            <div class="split">
              <div class="field">
                <label for="user-role">Rol</label>
                <select id="user-role" formControlName="role_id">
                  <option value="">Selecciona un rol</option>
                  @for (role of roles(); track role.id) {
                    <option [value]="role.id">{{ role.name }}</option>
                  }
                </select>
              </div>
              <div class="field">
                <label for="user-active">Estado</label>
                <select id="user-active" formControlName="is_active">
                  <option [ngValue]="true">Activo</option>
                  <option [ngValue]="false">Inactivo</option>
                </select>
              </div>
            </div>

            <div class="split">
              <div class="field">
                <label for="user-password">Password</label>
                <input id="user-password" type="password" formControlName="password" />
              </div>
              <div class="field">
                <label for="user-password-confirmation">Confirmar password</label>
                <input
                  id="user-password-confirmation"
                  type="password"
                  formControlName="password_confirmation"
                />
              </div>
            </div>

            @if (error()) {
              <p class="alert alert--danger">{{ error() }}</p>
            }

            <div class="cta-row">
              <button class="btn btn--primary" type="submit" [disabled]="saving()">
                @if (saving()) {
                  Guardando...
                } @else if (editingUserId()) {
                  Actualizar usuario
                } @else {
                  Crear usuario
                }
              </button>
              <button class="btn btn--ghost" type="button" (click)="resetForm()">Limpiar</button>
            </div>
          </form>
        </article>
      </section>
    </section>
  `,
})
export class UsersPageComponent {
  private readonly http = inject(HttpClient);
  private readonly fb = inject(FormBuilder);

  protected readonly users = signal<AuthUser[]>([]);
  protected readonly roles = signal<Role[]>([]);
  protected readonly search = signal('');
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly editingUserId = signal<number | null>(null);

  protected readonly form = this.fb.group({
    name: ['', [Validators.required]],
    display_name: [''],
    username: ['', [Validators.required]],
    email: [''],
    role_id: ['', [Validators.required]],
    is_active: [true, [Validators.required]],
    password: [''],
    password_confirmation: [''],
  });

  public constructor() {
    void this.load();
  }

  protected async load(): Promise<void> {
    await Promise.all([this.loadRoles(), this.loadUsers()]);
  }

  protected async loadRoles(): Promise<void> {
    const response = await firstValueFrom(
      this.http.get<RolesResponse>(`${API_BASE_URL}/roles`),
    );

    this.roles.set(response.data);
  }

  protected async loadUsers(): Promise<void> {
    const params = new URLSearchParams({
      per_page: '100',
    });

    if (this.search().trim() !== '') {
      params.set('search', this.search().trim());
    }

    const response = await firstValueFrom(
      this.http.get<UsersResponse>(`${API_BASE_URL}/users?${params.toString()}`),
    );

    this.users.set(response.data);
  }

  protected onSearch(event: Event): void {
    const target = event.target as HTMLInputElement;
    this.search.set(target.value);
    void this.loadUsers();
  }

  protected editUser(user: AuthUser): void {
    this.editingUserId.set(user.id);
    this.error.set(null);
    this.form.patchValue({
      name: user.name,
      display_name: user.display_name ?? '',
      username: user.username,
      email: user.email ?? '',
      role_id: String(user.role?.id ?? ''),
      is_active: user.is_active,
      password: '',
      password_confirmation: '',
    });
  }

  protected resetForm(): void {
    this.editingUserId.set(null);
    this.error.set(null);
    this.form.reset({
      name: '',
      display_name: '',
      username: '',
      email: '',
      role_id: '',
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

    this.saving.set(true);
    this.error.set(null);

    const payload = this.buildPayload();

    try {
      if (this.editingUserId()) {
        await firstValueFrom(
          this.http.patch(`${API_BASE_URL}/users/${this.editingUserId()}`, payload),
        );
      } else {
        await firstValueFrom(this.http.post(`${API_BASE_URL}/users`, payload));
      }

      this.resetForm();
      await this.loadUsers();
    } catch (error) {
      this.error.set(this.resolveError(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected async deleteUser(user: AuthUser): Promise<void> {
    if (!window.confirm(`Estas seguro de eliminar a ${user.display_name || user.name}?`)) {
      return;
    }

    try {
      await firstValueFrom(this.http.delete(`${API_BASE_URL}/users/${user.id}`));
      await this.loadUsers();
    } catch (error) {
      this.error.set(this.resolveError(error));
    }
  }

  private buildPayload(): Record<string, unknown> {
    const raw = this.form.getRawValue();

    const payload: Record<string, unknown> = {
      name: raw.name?.trim(),
      display_name: raw.display_name?.trim() || null,
      username: raw.username?.trim(),
      email: raw.email?.trim() || null,
      role_id: Number(raw.role_id),
      is_active: Boolean(raw.is_active),
    };

    if ((raw.password ?? '') !== '' || !this.editingUserId()) {
      payload['password'] = raw.password;
      payload['password_confirmation'] = raw.password_confirmation;
    }

    return payload;
  }

  private resolveError(error: unknown): string {
    if (
      typeof error === 'object' &&
      error !== null &&
      'error' in error &&
      typeof error.error === 'object' &&
      error.error !== null
    ) {
      const apiError = error.error as {
        message?: string;
        errors?: Record<string, string[]>;
      };

      const validationMessage = apiError.errors
        ? Object.values(apiError.errors).flat()[0]
        : null;

      return validationMessage ?? apiError.message ?? 'No se pudo completar la operacion.';
    }

    return 'No se pudo completar la operacion.';
  }
}
