import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-login-page',
  imports: [ReactiveFormsModule, RouterLink],
  template: `
    <div
      class="min-h-screen bg-white px-6 py-10 text-slate-900 sm:px-8 lg:px-10"
    >
      <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-7xl items-center justify-center">
        <div class="w-full max-w-md">
          <div class="rounded-[2rem] border border-slate-200 bg-white p-2 shadow-[0_30px_80px_rgba(15,23,42,0.10)]">
            <article class="rounded-[1.6rem] bg-white px-7 py-8 sm:px-8 sm:py-9">
              <div class="mb-8 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50 text-sm font-semibold tracking-[0.22em] text-emerald-700"
                  >
                    VP
                  </div>

                  <div>
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.28em] text-emerald-700/80">
                      VentasPOS
                    </p>
                    <h1 class="text-lg font-semibold tracking-tight text-slate-900">Iniciar sesion</h1>
                  </div>
                </div>

                <span
                  class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-[0.7rem] font-medium text-emerald-700"
                >
                  Seguro
                </span>
              </div>

              <div class="mb-8 space-y-2">
                <p class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-[2rem]">
                  Acceso al sistema
                </p>
                <p class="text-sm leading-6 text-slate-500">
                  Ingresa tus credenciales para continuar.
                </p>
              </div>

              @if (legacyNotice()) {
                <p
                  class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800"
                >
                  {{ legacyNotice() }}
                </p>
              }

              <form class="space-y-5" [formGroup]="form" (ngSubmit)="submit()">
                <label class="group relative block">
                  <input
                    id="login-user"
                    type="text"
                    formControlName="login"
                    placeholder=" "
                    autocomplete="username"
                    class="peer h-14 w-full rounded-2xl border border-slate-200 bg-white px-4 pt-5 text-base text-slate-900 outline-none transition placeholder:text-transparent hover:border-slate-300 focus:border-emerald-500 focus:shadow-[0_0_0_4px_rgba(16,185,129,0.08)]"
                  />
                  <span
                    class="pointer-events-none absolute left-4 top-4 origin-left text-sm text-slate-400 transition-all duration-200 peer-placeholder-shown:top-[1.15rem] peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-focus:top-2.5 peer-focus:scale-90 peer-focus:text-xs peer-focus:font-medium peer-focus:text-emerald-700 peer-not-placeholder-shown:top-2.5 peer-not-placeholder-shown:scale-90 peer-not-placeholder-shown:text-xs peer-not-placeholder-shown:font-medium peer-not-placeholder-shown:text-slate-500"
                  >
                    Usuario o correo
                  </span>
                </label>

                <label class="group relative block">
                  <input
                    id="login-password"
                    type="password"
                    formControlName="password"
                    placeholder=" "
                    autocomplete="current-password"
                    class="peer h-14 w-full rounded-2xl border border-slate-200 bg-white px-4 pt-5 text-base text-slate-900 outline-none transition placeholder:text-transparent hover:border-slate-300 focus:border-emerald-500 focus:shadow-[0_0_0_4px_rgba(16,185,129,0.08)]"
                  />
                  <span
                    class="pointer-events-none absolute left-4 top-4 origin-left text-sm text-slate-400 transition-all duration-200 peer-placeholder-shown:top-[1.15rem] peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-focus:top-2.5 peer-focus:scale-90 peer-focus:text-xs peer-focus:font-medium peer-focus:text-emerald-700 peer-not-placeholder-shown:top-2.5 peer-not-placeholder-shown:scale-90 peer-not-placeholder-shown:text-xs peer-not-placeholder-shown:font-medium peer-not-placeholder-shown:text-slate-500"
                  >
                    Contrasena
                  </span>
                </label>

                @if (error()) {
                  <p
                    class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-700"
                  >
                    {{ error() }}
                  </p>
                }

                <button
                  class="inline-flex h-14 w-full items-center justify-center rounded-2xl bg-emerald-600 text-sm font-semibold text-white shadow-[0_22px_40px_rgba(16,185,129,0.18)] transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
                  type="submit"
                  [disabled]="loading()"
                >
                  @if (loading()) {
                    Entrando...
                  } @else {
                    Ingresar
                  }
                </button>
              </form>

              <div class="mt-8 flex items-center justify-between gap-4">
                <a
                  routerLink="/help"
                  class="inline-flex items-center rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                >
                  ¿Necesitas ayuda?
                </a>
              </div>
            </article>
          </div>
        </div>
      </div>
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
      case 'auth-legacy-retired':
        return 'El login heredado ya no transfiere sesiones al panel nuevo. Ingresa directamente desde este acceso Angular.';
      case 'auth-expired':
        return 'La sesion anterior expiro o el token CSRF legacy ya no era valido. Inicia sesion otra vez desde aqui.';
      case 'signed-out':
        return 'La sesion fue cerrada correctamente. Ya puedes volver a ingresar al panel nuevo.';
      default:
        return null;
    }
  }
}
