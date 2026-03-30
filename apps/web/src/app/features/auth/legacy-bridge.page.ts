import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-legacy-bridge-page',
  template: `
    <div class="auth-shell">
      <section class="surface auth-card auth-card--bridge">
        <div class="page-header">
          <div class="page-header__copy">
            <span class="page-kicker">Legacy Bridge</span>
            <h1 class="page-title">Conectando tu sesion al panel nuevo</h1>
            <p class="page-description">
              Estamos trasladando tu acceso activo del sistema anterior hacia Angular + Laravel.
            </p>
          </div>
        </div>

        @if (loading()) {
          <p class="bridge-state">Validando sesion y preparando el modulo solicitado...</p>
        } @else if (error()) {
          <div class="stack">
            <p class="alert alert--danger">{{ error() }}</p>
            <button class="btn btn--primary" type="button" (click)="goToLogin()">
              Ir al login nuevo
            </button>
          </div>
        }
      </section>
    </div>
  `,
})
export class LegacyBridgePageComponent {
  private readonly auth = inject(AuthService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);

  public constructor() {
    void this.consumeBridge();
  }

  protected async goToLogin(): Promise<void> {
    await this.router.navigate(['/login'], {
      queryParams: { redirect: this.resolveRedirect(this.route.snapshot.queryParamMap.get('redirect')) },
    });
  }

  private async consumeBridge(): Promise<void> {
    const bridgeToken = this.route.snapshot.queryParamMap.get('token');
    const redirect = this.resolveRedirect(this.route.snapshot.queryParamMap.get('redirect'));

    if (!bridgeToken) {
      this.error.set('La sesion heredada no incluyo un token valido.');
      this.loading.set(false);
      return;
    }

    try {
      await this.auth.boot();

      if (!this.auth.isAuthenticated()) {
        await this.auth.loginWithLegacyBridge(bridgeToken);
      }

      await this.router.navigateByUrl(redirect);
    } catch (error) {
      this.error.set(this.resolveError(error));
      this.loading.set(false);
    }
  }

  private resolveRedirect(value: string | null): string {
    if (!value || !value.startsWith('/')) {
      return '/dashboard';
    }

    return value;
  }

  private resolveError(error: unknown): string {
    if (error instanceof HttpErrorResponse) {
      return (
        error.error?.errors?.token?.[0] ??
        error.error?.message ??
        'No se pudo enlazar la sesion legacy con el panel nuevo.'
      );
    }

    return 'No se pudo enlazar la sesion legacy con el panel nuevo.';
  }
}
