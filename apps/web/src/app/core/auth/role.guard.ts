import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from './auth.service';

export function requireRoles(...roles: string[]): CanActivateFn {
  return async () => {
    const auth = inject(AuthService);
    const router = inject(Router);

    await auth.boot();

    return auth.hasRole(...roles) ? true : router.createUrlTree([auth.defaultRoute()]);
  };
}
