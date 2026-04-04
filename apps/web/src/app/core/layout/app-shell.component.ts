import { Component, computed, inject, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../auth/auth.service';
import { NAVIGATION_ITEMS } from '../navigation/navigation.items';

@Component({
  selector: 'app-shell',
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  template: `
    <div class="app-shell">
      <button
        class="app-shell__backdrop"
        type="button"
        [class.is-open]="menuOpen()"
        (click)="closeMenu()"
        aria-label="Cerrar menu lateral"
      ></button>

      <aside class="app-sidebar" [class.is-open]="menuOpen()">
        <div class="app-sidebar__panel">
          <div class="app-sidebar__brand">
            <div class="brand-mark brand-mark--light" aria-hidden="true">
              <span></span>
              <span></span>
              <span></span>
            </div>

            <div class="brand-wordmark">
              <span class="app-sidebar__eyebrow">VentasPOS</span>
              <strong class="app-sidebar__title">Panel administrativo</strong>
              <span class="app-sidebar__caption">Operacion comercial centralizada.</span>
            </div>
          </div>

          <div class="app-sidebar__section">
            <small class="app-sidebar__section-label">Navegacion</small>
          </div>

          <nav class="app-nav">
            @for (item of navigation(); track item.route) {
              <a
                [routerLink]="item.route"
                routerLinkActive="is-active"
                class="app-nav__link"
                (click)="closeMenu()"
              >
                <span class="app-nav__glyph" aria-hidden="true">
                  @switch (item.icon) {
                    @case ('dashboard') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="3" width="8" height="8" rx="2"></rect>
                        <rect x="13" y="3" width="8" height="5" rx="2"></rect>
                        <rect x="13" y="10" width="8" height="11" rx="2"></rect>
                        <rect x="3" y="13" width="8" height="8" rx="2"></rect>
                      </svg>
                    }
                    @case ('products') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z"></path>
                        <path d="M12 20v-8.5"></path>
                        <path d="M4.5 8.5 12 13l7.5-4.5"></path>
                      </svg>
                    }
                    @case ('assets') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="4" y="5" width="16" height="14" rx="2"></rect>
                        <path d="M8 9h8"></path>
                        <path d="M8 13h5"></path>
                      </svg>
                    }
                    @case ('customers') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M16 19a4 4 0 0 0-8 0"></path>
                        <circle cx="12" cy="9" r="3"></circle>
                        <path d="M5 19a3 3 0 0 1 3-3"></path>
                        <path d="M19 19a3 3 0 0 0-3-3"></path>
                      </svg>
                    }
                    @case ('cash') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="6" width="18" height="12" rx="2"></rect>
                        <circle cx="12" cy="12" r="2.5"></circle>
                        <path d="M7 9h.01"></path>
                        <path d="M17 15h.01"></path>
                      </svg>
                    }
                    @case ('purchases') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M6 6h15l-1.5 8h-11Z"></path>
                        <path d="M6 6 5 3H3"></path>
                        <circle cx="9" cy="19" r="1.5"></circle>
                        <circle cx="18" cy="19" r="1.5"></circle>
                      </svg>
                    }
                    @case ('suppliers') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 17V7l5 3v7l-5-3Z"></path>
                        <path d="M8 10h7l3 3v4H8"></path>
                        <circle cx="9" cy="18" r="1.5"></circle>
                        <circle cx="17" cy="18" r="1.5"></circle>
                      </svg>
                    }
                    @case ('sales') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M5 7h14l-1 10H6L5 7Z"></path>
                        <path d="M9 7V5a3 3 0 0 1 6 0v2"></path>
                      </svg>
                    }
                    @case ('reports') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M5 19V10"></path>
                        <path d="M12 19V5"></path>
                        <path d="M19 19v-7"></path>
                      </svg>
                    }
                    @case ('settings') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a2 2 0 1 1-4 0v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4a2 2 0 1 1 0-4h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9V4a2 2 0 1 1 4 0v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6H20a2 2 0 1 1 0 4h-.2a1 1 0 0 0-.9.6Z"></path>
                      </svg>
                    }
                    @case ('users') {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M16 19a4 4 0 0 0-8 0"></path>
                        <circle cx="12" cy="9" r="3"></circle>
                        <path d="M18 18a3 3 0 0 0-2.3-2.9"></path>
                        <path d="M6.3 15.1A3 3 0 0 0 4 18"></path>
                      </svg>
                    }
                    @default {
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="8"></circle>
                      </svg>
                    }
                  }
                </span>
                <span class="app-nav__label">{{ item.label }}</span>
              </a>
            }
          </nav>

          <div class="app-sidebar__footer">
            <small class="app-sidebar__footer-label">Sesion actual</small>
            <div class="app-sidebar__account">
              <span class="app-sidebar__account-avatar">
                {{ initialsFor(auth.user()?.display_name || auth.user()?.name || 'SS') }}
              </span>
              <div class="app-sidebar__account-copy">
                <strong>{{ auth.user()?.display_name || auth.user()?.name || 'Sin sesion' }}</strong>
                <small>{{ auth.user()?.role?.name || 'Sin rol' }}</small>
              </div>
            </div>
          </div>
        </div>
      </aside>

      <div class="app-main">
        <header class="app-topbar">
          <div class="app-topbar__cluster">
            <button class="app-topbar__menu" type="button" (click)="toggleMenu()">
              <span></span>
              <span></span>
              <span></span>
            </button>

            <div class="app-topbar__title">
              <span class="app-topbar__eyebrow">Panel administrativo</span>
              <strong>{{ currentSectionLabel() }}</strong>
              <span>{{ currentSectionDescription() }}</span>
            </div>
          </div>

          <div class="app-topbar__meta">
            <span class="app-topbar__status">
              <span class="app-topbar__status-dot"></span>
              Operativo
            </span>
            @if (auth.user(); as user) {
              <div class="app-topbar__user">
                <span class="app-topbar__avatar">{{ initialsFor(user.display_name || user.name) }}</span>
                <span class="app-topbar__user-copy">
                  <strong>{{ user.display_name || user.name }}</strong>
                  <small>{{ user.role?.name || 'Sin rol' }}</small>
                </span>
              </div>
              <button class="btn btn--ghost" type="button" (click)="logout()">Salir</button>
            }
            <small class="app-topbar__date">{{ today }}</small>
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
  private readonly router = inject(Router);
  protected readonly menuOpen = signal(false);

  protected readonly navigation = computed(() =>
    NAVIGATION_ITEMS.filter((item) => !item.adminOnly || this.auth.isAdmin()),
  );

  protected readonly today = new Intl.DateTimeFormat('es-EC', {
    dateStyle: 'full',
  }).format(new Date());

  protected toggleMenu(): void {
    this.menuOpen.update((value) => !value);
  }

  protected closeMenu(): void {
    this.menuOpen.set(false);
  }

  protected glyphFor(label: string): string {
    return label
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((segment) => segment[0]?.toUpperCase() ?? '')
      .join('');
  }

  protected initialsFor(value: string): string {
    return value
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((segment) => segment[0]?.toUpperCase() ?? '')
      .join('');
  }

  protected currentSectionLabel(): string {
    const item = this.currentNavigationItem();
    return item?.label ?? 'Resumen general';
  }

  protected currentSectionDescription(): string {
    const item = this.currentNavigationItem();
    return item?.description ?? 'Seguimiento operativo y control diario del sistema.';
  }

  protected async logout(): Promise<void> {
    await this.auth.logout({ redirectTo: '/login?legacy=signed-out&logout=1' });
  }

  private currentNavigationItem() {
    const currentUrl = this.router.url;

    return NAVIGATION_ITEMS.find((item) => {
      if (item.route === '/dashboard') {
        return currentUrl === '/' || currentUrl.startsWith('/dashboard');
      }

      return currentUrl.startsWith(item.route);
    });
  }
}
