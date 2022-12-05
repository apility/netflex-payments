<?php

namespace Apility\Payment\Http\Middlewares;

use Apility\Payment\Facades\Payment;
use Apility\Payment\Routing\Payment as RoutingPayment;
use Closure;
use Illuminate\Http\Request;
use Netflex\Commerce\Contracts\Order;
use Netflex\Commerce\Order as OrderAlias;

class EnsureOrderNotCompleted
{
    public function handle(Request $request, Closure $next)
    {
        /** @var Order $order */
        if ($request->has('secret')) {
            $order = OrderAlias::retrieveBySecret($request->get('secret'));
        } else {
            $order = $request->route()->parameter('order');
            $order = is_string($order) ? OrderAlias::retrieveBySecret($order) : $order;
        }

        /** @var Order $order */

        abort_unless($order, 500);

        if ($order->isLocked()) {
            return redirect(RoutingPayment::route('receipt', $order));
        }

        return $next($request);
    }
}
