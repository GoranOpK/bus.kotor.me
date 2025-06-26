<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
protected $except = [
    'api/*',
	'/procesiraj-placanje',
    'procesiraj-placanje',
    'public/procesiraj-placanje', // dodaj i ovo!
    '/public/procesiraj-placanje',
	'payment/callback*',
    '*',
];
}