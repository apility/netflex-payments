<?php

namespace Apility\Payment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string route($name, $parameters = [], $absolute = true)
 */
class Router extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payment.router';
    }
}
