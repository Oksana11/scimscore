<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Base;

/**
 * CSRF protection without the JavaScript-readable XSRF-TOKEN cookie: our scripts send the
 * token from the form (_token / X-CSRF-TOKEN), so no cookie has to be readable by JS.
 */
class PreventRequestForgery extends Base
{
    protected $addHttpCookie = false;
}
