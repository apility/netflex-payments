<?php

namespace Apility\Payment\Http\Middlewares;

use Illuminate\Http\Request;
use Apility\Payment\Facades\Payment;
use Netflex\Commerce\Contracts\Order;
use Netflex\Commerce\Order as OrderAlias;

class EnsurePaymentsAreSyncedMiddleware extends BaseEnsurePaymentsAreSyncedMiddleware
{
    function resolveOrder(?Request $request): ?Order
    {
        /** @var Order $order */
        if ($request->has('secret')) {
            return OrderAlias::retrieveBySecret($request->get('secret'));
        } else {
            $order = $request->route()->parameter('order');
            return is_string($order) ? OrderAlias::retrieveBySecret($order) : $order;
        }

        if ($order instanceof Order) {
            $order->refreshOrder();
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
    }
}
