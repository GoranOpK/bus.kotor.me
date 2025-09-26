<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizeAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Privremeno dozvoli SVIMA koji dođu do ove tačke (bez provjere)
        return $next($request);
    }
}