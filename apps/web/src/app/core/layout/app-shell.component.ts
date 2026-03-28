import { Component, computed, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../auth/auth.service';
import { NAVIGATION_ITEMS } from '../navigation/navigation.items';

@Component({
  selector: 'app-shell',
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  template: `
    <div class="app-shell">
      <aside class="app-sidebar">
        <div class="app-sidebar__brand">
          <span class="app-sidebar__eyebrow">VentasPOS 2026</span>
          <strong class="app-sidebar__title">Laravel 13 + Angular 21</strong>
          <span class="app-sidebar__caption">
            Shell inicial del nuevo panel administrativo sobre PostgreSQL 18.
          </span>
        </div>

        <nav class="app-nav">
          @for (item of navigation(); track item.route) {
            <a
              [routerLink]="item.route"
              routerLinkActive="is-active"
              class="app-nav__link"
            >
              <span class="app-nav__label">{{ item.label }}</span>
              <span class="app-nav__description">{{ item.description }}</span>
            </a>
          }
        </nav>
      </aside>

      <div class="app-main">
        <header class="app-topbar">
          <div class="app-topbar__title">
            <strong>Base de migracion en progreso</strong>
            <span>Legacy PHP convive temporalmente con la nueva plataforma.</span>
          </div>
          <div class="app-topbar__meta">
            @if (auth.user(); as user) {
              <span>{{ user.display_name || user.name }} · {{ user.role?.name || 'Sin rol' }}</span>
              <button class="btn btn--ghost" type="button" (click)="logout()">Salir</button>
            }
            <small>{{ today }}</small>
          </div>
        </header>

        <main class="app-content">
          <router-outlet></router-outlet>
        </main>
      </div>
    </div>
  `,
})
export class AppShellComponent {
  protected readonly auth = inject(AuthService);

  protected readonly navigation = computed(() =>
    NAVIGATION_ITEMS.filter((item) => !item.adminOnly || this.auth.isAdmin()),
  );

  protected readonly today = new Intl.DateTimeFormat('es-EC', {
    dateStyle: 'full',
  }).format(new Date());

  protected async logout(): Promise<void> {
    await this.auth.logout();
  }
}
