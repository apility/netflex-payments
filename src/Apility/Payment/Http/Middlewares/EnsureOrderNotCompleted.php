<?php

namespace Apility\Payment\Http\Middlewares;

use Illuminate\Http\Request;
use Netflex\Commerce\Order;


class EnsureOrderNotCompleted extends BaseEnsureOrderNotCompleted
{
    function resolveOrder(?Request $request): ?Order
    {
        if ($request->has('secret')) {
            return Order::retrieveBySecret($request->get('secret'));
        } else {
            /** @var Order $order */
            $order = $request->route()->parameter('order');
            return is_string($order) ? Order::retrieveBySecret($order) : $order;
        }
    }
}
