import { HttpClient } from '@angular/common/http';
import { computed, inject, Injectable, signal } from '@angular/core';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ACCESS_TOKEN_STORAGE_KEY, API_BASE_URL } from '../config/api.config';
import { AuthUser, LoginResponse, MeResponse } from './auth.types';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);

  private readonly booted = signal(false);

  readonly user = signal<AuthUser | null>(null);
  readonly status = signal<'idle' | 'loading' | 'authenticated' | 'guest'>('idle');

  readonly isAuthenticated = computed(() => this.user() !== null);
  readonly isAdmin = computed(() => this.user()?.role?.slug === 'admin');

  async boot(): Promise<void> {
    if (this.booted()) {
      return;
    }

    const token = this.getToken();

    if (!token) {
      this.status.set('guest');
      this.booted.set(true);
      return;
    }

    try {
      const response = await firstValueFrom(
        this.http.get<MeResponse>(`${API_BASE_URL}/auth/me`),
      );

      this.user.set(response.data);
      this.status.set('authenticated');
    } catch {
      this.clearSession();
    } finally {
      this.booted.set(true);
    }
  }

  async login(payload: { login: string; password: string }): Promise<AuthUser> {
    this.status.set('loading');

    const response = await firstValueFrom(
      this.http.post<LoginResponse>(`${API_BASE_URL}/auth/login`, {
        ...payload,
        device_name: 'ventaspos-web',
      }),
    );

    this.setToken(response.meta.token);
    this.user.set(response.data.user);
    this.status.set('authenticated');
    this.booted.set(true);

    return response.data.user;
  }

  async logout(options: { redirectTo?: string | null } = {}): Promise<void> {
    const { redirectTo = '/login' } = options;

    try {
      if (this.getToken()) {
        await firstValueFrom(this.http.post(`${API_BASE_URL}/auth/logout`, {}));
      }
    } catch {
      // Ignoramos errores de red al cerrar la sesion local.
    }

    this.clearSession();

    if (redirectTo) {
      await this.router.navigateByUrl(redirectTo);
    }
  }

  getToken(): string | null {
    if (typeof localStorage === 'undefined') {
      return null;
    }

    return localStorage.getItem(ACCESS_TOKEN_STORAGE_KEY);
  }

  private setToken(token: string): void {
    if (typeof localStorage === 'undefined') {
      return;
    }

    localStorage.setItem(ACCESS_TOKEN_STORAGE_KEY, token);
  }

  clearSession(): void {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem(ACCESS_TOKEN_STORAGE_KEY);
    }

    this.user.set(null);
    this.status.set('guest');
    this.booted.set(true);
  }
}
