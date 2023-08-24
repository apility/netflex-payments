<?php

namespace Apility\Payment\Http\Middlewares;

use Apility\Payment\Facades\Payment;
use Apility\Payment\Routing\Payment as RoutingPayment;
use Closure;
use Illuminate\Http\Request;
use Netflex\Commerce\Contracts\Order;
use Netflex\Commerce\Order as OrderAlias;

abstract class BaseEnsureOrderNotCompleted
{

    /**
     * Resolves the order we want to access from the request
     *
     * If null is returned we throw a generic 500 exception in the next step
     */
    abstract function resolveOrder(?Request $request): ?Order;

    public function handle(Request $request, Closure $next)
    {

        $order = $this->resolveOrder($request);
        abort_unless($order, 500);

        if ($order->isLocked()) {
            return redirect(RoutingPayment::route('receipt', $order, false));
        }

        return $next($request);
    }
}
