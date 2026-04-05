<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowedRoles = array_values(array_filter($roles, static fn (string $role): bool => trim($role) !== ''));

        if (! $user || $allowedRoles === [] || ! in_array($user->role?->slug, $allowedRoles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes permisos para realizar esta accion.');
        }

        return $next($request);
    }
}
