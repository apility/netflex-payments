<?php

namespace Apility\Payment\Http\Middlewares;

use Apility\Payment\Facades\Payment;
use Closure;
use Illuminate\Http\Request;
use Netflex\Commerce\Contracts\Order;
use Netflex\Commerce\Order as OrderAlias;

class EnsurePaymentsAreSyncedMiddleware
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

        $order->refreshOrder();

        if ($order instanceof Order) {

            $refresh = false;

            foreach ($order->getOrderPayments() as $payment) {
                if ($payment->isLocked()) {
                    continue;
                }

                if ($payment->getIsPending() && ($paymentObject = Payment::resolve($payment))) {
                    if ($order->updatePayment($paymentObject)) {
                        $refresh = true;
                    }
                }
            }

            if ($refresh)
                $order->refreshOrder();
        }

        return $next($request);
    }
}
