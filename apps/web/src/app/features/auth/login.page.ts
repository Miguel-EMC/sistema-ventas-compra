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

        @if (legacyNotice()) {
          <p class="alert alert--info">{{ legacyNotice() }}</p>
        }

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
  protected readonly legacyNotice = signal<string | null>(null);
  protected readonly loading = signal(false);

  protected readonly form = this.fb.nonNullable.group({
    login: ['admin', [Validators.required]],
    password: ['password', [Validators.required]],
  });

  public constructor() {
    void this.bootstrap();
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

  private async bootstrap(): Promise<void> {
    this.legacyNotice.set(this.resolveLegacyNotice(this.route.snapshot.queryParamMap.get('legacy')));

    if (this.route.snapshot.queryParamMap.get('logout') === '1') {
      await this.auth.logout({ redirectTo: null });
      return;
    }

    await this.auth.boot();

    if (this.auth.isAuthenticated()) {
      const redirect = this.route.snapshot.queryParamMap.get('redirect') || '/dashboard';
      await this.router.navigateByUrl(redirect);
    }
  }

  private resolveLegacyNotice(value: string | null): string | null {
    switch (value) {
      case 'shell-login':
        return 'El acceso PHP legacy fue retirado. Este login Angular ya es la entrada principal del sistema.';
      case 'auth-required':
        return 'El modulo que intentaste abrir ya fue migrado. Inicia sesion aqui para continuar en la plataforma nueva.';
      case 'auth-missing-fields':
        return 'El formulario legacy no envio usuario y password completos. Continua desde este acceso nuevo.';
      case 'auth-invalid':
        return 'Las credenciales del acceso legacy no fueron validas. Intenta nuevamente desde este login.';
      case 'auth-expired':
        return 'La sesion anterior expiro o el token CSRF legacy ya no era valido. Inicia sesion otra vez desde aqui.';
      case 'signed-out':
        return 'La sesion fue cerrada correctamente. Ya puedes volver a ingresar al panel nuevo.';
      default:
        return null;
    }
  }
}
