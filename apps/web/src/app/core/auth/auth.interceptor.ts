import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from './auth.service';

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const auth = inject(AuthService);
  const token = auth.getToken();

  const headers = token
    ? request.headers.set('Authorization', `Bearer ${token}`).set('Accept', 'application/json')
    : request.headers.set('Accept', 'application/json');

  return next(request.clone({ headers }));
};
