<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role?->slug !== 'admin') {
            abort(Response::HTTP_FORBIDDEN, 'No tienes permisos para realizar esta accion.');
        }

        return $next($request);
    }
}
