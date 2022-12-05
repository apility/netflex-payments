<?php

namespace Apility\Payment\Routing;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Contracts\PaymentProcessor;
use Apility\Payment\Contracts\PaymentController;
use Apility\Payment\Http\Middlewares\EnsureOrderNotCompleted;
use Apility\Payment\Http\Middlewares\EnsurePaymentsAreSyncedMiddleware;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route as RouteRegistrar;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Netflex\Commerce\Contracts\Order;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class Payment
{
    protected static $routes = [];
    protected static string $pdfRoutePath = 'receipt';

    public static function controller(): string
    {
        return App::make('payment.controller');
    }

    public static function routes(string $prefix = 'payment', ?callable $callback = null)
    {
        RouteRegistrar::group(['prefix' => $prefix], function () use ($callback) {
            RouteRegistrar::group(['prefix' => '{order}', 'middleware' => EnsurePaymentsAreSyncedMiddleware::class], function () use ($callback) {
                if (!$callback) {
                    static::registerPaymentRoute();
                    static::registerCallbackRoute();
                    static::registerReceiptRoute();
                    static::registerReceiptPdfRoute();
                } else {
                    $callback();
                }

                $pdfRoutePath = static::$pdfRoutePath;

                $routes = [
                    'pay' => fn () => static::registerPaymentRoute(),
                    'callback' => fn () => static::registerCallbackRoute(),
                    'receipt' => fn () => static::registerReceiptRoute(),
                    'receipt.pdf' => fn () => static::registerReceiptPdfRoute(static::$pdfRoutePath),
                ];

                foreach ($routes as $name => $register) {
                    if (!isset(static::$routes[$name]) || empty(static::$routes[$name])) {
                        $register();
                    }
                }

                static::$pdfRoutePath = $pdfRoutePath;
            });
        });
    }

    public static function create(Order $order): string
    {
        return static::route('pay', ['order' => $order]);
    }

    public static function callback(Order $order, PaymentContract $payment): string
    {
        $processor = $payment->getProcessor();

        return static::route('callback', array_filter([
            'order' => $order,
            'processor' => $processor->getProcessor(),
            'paymentId' => $payment->getPaymentId()
        ]));
    }

    public static function route($name, $parameters = [], $absolute = true)
    {
        if (isset(static::$routes[$name])) {
            if (count(static::$routes[$name])) {
                if (count(static::$routes[$name]) === 1) {
                    return route(static::$routes[$name][0]->getName(), $parameters, $absolute);
                }

                if ($currentRoute = Request::route()) {
                    $routes = collect(static::$routes[$name])->keyBy(fn (Route $route) => $route->getName())
                        ->filter(function (Route $route) use ($name, $currentRoute) {
                            $namePrefix = $currentRoute->getName();
                            $parts = explode('.', $namePrefix);

                            while (count($parts)) {
                                if (Str::startsWith($route->getName(), implode('.', $parts))) {
                                    $namePrefix = implode('.', $parts);
                                    break;
                                }

                                array_pop($parts);
                            }

                            $routeName = "$namePrefix.payment.$name";

                            if ($route->getName() === $routeName) {
                                return true;
                            }
                        });

                    if ($routes->count()) {
                        return route($routes->first()->getName(), $parameters, $absolute);
                    }
                }

                return route(static::$routes[$name][0]->getName(), $parameters, $absolute);
            }
        }

        throw new RouteNotFoundException("Payment route [$name] not found.");
    }

    public static function registerPaymentRoute(string $path = ''): Route
    {
        $registedRoute = null;

        RouteRegistrar::group(['middleware' => EnsureOrderNotCompleted::class], function () use ($path, &$registedRoute) {
            $registedRoute = static::registerRoute('get', $path, 'pay', 'pay');
        });

        return $registedRoute;
    }

    public static function registerCallbackRoute(string $path = 'callback'): Route
    {
        return static::registerRoute(['get', 'post'], '{processor}/' . $path, 'callback', 'callback');
    }

    public static function registerReceiptRoute(string $path = 'receipt'): Route
    {
        static::$pdfRoutePath = $path;
        return static::registerRoute('get', $path, 'receipt', 'receipt');
    }

    public static function registerReceiptPdfRoute(string $path = 'receipt'): Route
    {
        return static::registerRoute('get', $path . '.pdf', 'receiptPdf', 'receipt.pdf');
    }

    /**
     * @param string|string[] $methods
     * @param string $path
     * @param string $action
     * @param string $name
     */
    protected static function registerRoute($methods, string $path, string $action, string $name): Route
    {
        /** @var PaymentController */
        $controller = static::controller();
        $methods = !is_array($methods) ? [$methods] : $methods;
        static::$routes[$name] = static::$routes[$name] ?? [];

        return static::$routes[$name][] = RouteRegistrar::match($methods, $path, [$controller, $action])->name('payment.' . $name);
    }

    public function extend(string $alias, PaymentProcessor $processor)
    {
        App::bind('payment.processors.' . $alias, $processor);
    }
}
