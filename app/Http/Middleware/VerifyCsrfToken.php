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
<<<<<<< HEAD

    protected $except = [
        'api/*',
        'payment/callback',
        'payment/callback/*',
        'payment/*',
        'callback',
        'callback/*',
        'test-callback',
        'test-post',
        'test-simple',
    ];
=======
protected $except = [
    'api/*',
	'/procesiraj-placanje',
    'procesiraj-placanje',
    'public/procesiraj-placanje', // dodaj i ovo!
    '/public/procesiraj-placanje',
	'payment/callback*',
    '*',
];
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
}