<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $roles)
    {
        $roles = array_map('trim', explode(',', $roles));   // ← transforma em array

        if (!$request->user()?->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Acesso negado. Role necessária: ' . implode('|', $roles),
            ], 403);
        }

        return $next($request);
    }
}
