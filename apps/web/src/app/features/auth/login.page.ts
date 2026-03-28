import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-login-page',
  imports: [ReactiveFormsModule],
  template: `
    <div class="auth-shell">
      <section class="auth-panel">
        <div class="stack">
          <span class="page-kicker">Nueva plataforma</span>
          <h1 class="page-title">Acceso real al backend migrado.</h1>
          <p class="page-description">
            Este login ya habla con Laravel 13 por medio de Sanctum y deja lista la base para el
            resto de modulos.
          </p>
        </div>

        <ul class="bullet-list">
          <li>
            <strong>Usuario base</strong>
            admin
          </li>
          <li>
            <strong>Password base</strong>
            password
          </li>
          <li>
            <strong>Rol inicial</strong>
            Administrador
          </li>
        </ul>
      </section>

      <section class="surface auth-card">
        <div class="page-header">
          <div class="page-header__copy">
            <span class="page-kicker">Auth + Users</span>
            <h2 class="page-title">Entrar al panel nuevo</h2>
            <p class="page-description">
              Usa el usuario administrador sembrado por la API para validar el primer modulo real.
            </p>
          </div>
        </div>

        <form class="form-grid" [formGroup]="form" (ngSubmit)="submit()">
          <div class="field">
            <label for="login-user">Correo o usuario</label>
            <input id="login-user" type="text" formControlName="login" placeholder="admin" />
          </div>

          <div class="field">
            <label for="login-password">Password</label>
            <input
              id="login-password"
              type="password"
              formControlName="password"
              placeholder="password"
            />
          </div>

          @if (error()) {
            <p class="alert alert--danger">{{ error() }}</p>
          }

          <div class="cta-row">
            <button class="btn btn--primary" type="submit" [disabled]="loading()">
              @if (loading()) {
                Entrando...
              } @else {
                Iniciar sesion
              }
            </button>
          </div>
        </form>
      </section>
    </div>
  `,
})
export class LoginPageComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  protected readonly error = signal<string | null>(null);
  protected readonly loading = signal(false);

  protected readonly form = this.fb.nonNullable.group({
    login: ['admin', [Validators.required]],
    password: ['password', [Validators.required]],
  });

  public constructor() {
    void this.auth.boot().then(() => {
      if (this.auth.isAuthenticated()) {
        void this.router.navigateByUrl('/dashboard');
      }
    });
  }

  protected async submit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    try {
      await this.auth.login(this.form.getRawValue());

      const redirect = this.route.snapshot.queryParamMap.get('redirect') || '/dashboard';
      await this.router.navigateByUrl(redirect);
    } catch (error) {
      this.error.set(this.resolveError(error));
    } finally {
      this.loading.set(false);
    }
  }

  private resolveError(error: unknown): string {
    if (error instanceof HttpErrorResponse) {
      return (
        error.error?.errors?.login?.[0] ??
        error.error?.message ??
        'No se pudo iniciar sesion.'
      );
    }

    return 'No se pudo iniciar sesion.';
  }
}
