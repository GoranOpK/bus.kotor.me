<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizeAdminOrControl
{
    /**
     * Dozvoljava pristup korisnicima koji su admin ili readonly admin ('control').
     */
    public function handle(Request $request, Closure $next)
    {
        // Debug informacije
        \Log::info('AuthorizeAdminOrControl middleware', [
            'user' => $request->user(),
            'username' => $request->user() ? $request->user()->username : null,
            'auth_header' => $request->header('Authorization'),
            'path' => $request->path(),
        ]);

        // Proveri da li postoji Sanctum user (admin ili readonly admin)
        if ($request->user() && in_array($request->user()->username, ['admin', 'control'])) {
            return $next($request);
        }

        abort(403, 'Unauthorized action.');
    }
}