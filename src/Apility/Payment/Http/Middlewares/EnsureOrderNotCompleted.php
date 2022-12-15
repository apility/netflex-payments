<?php

namespace Apility\Payment\Http\Middlewares;

use Apility\Payment\Facades\Payment;
use Apility\Payment\Routing\Payment as RoutingPayment;
use Closure;
use Illuminate\Http\Request;
use Netflex\Commerce\Order;


class EnsureOrderNotCompleted extends BaseEnsureOrderNotCompleted
{
    function resolveOrder(?Request $request): ?Order
    {
        /** @var Order $order */
        if ($request->has('secret')) {
            return Order::retrieveBySecret($request->get('secret'));
        } else {
            $order = $request->route()->parameter('order');
            return is_string($order) ? Order::retrieveBySecret($order) : $order;
        }
    }
}
