import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { AuthService } from './auth.service';

export const guestGuard: CanActivateFn = async (route) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (route.queryParamMap.get('logout') === '1') {
    return true;
  }

  await auth.boot();

  return auth.isAuthenticated() ? router.createUrlTree([auth.defaultRoute()]) : true;
};
