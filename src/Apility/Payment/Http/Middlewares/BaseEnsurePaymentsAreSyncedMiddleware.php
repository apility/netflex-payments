<?php

namespace Apility\Payment\Http\Middlewares;

use Apility\Payment\Facades\Payment;
use Closure;
use Illuminate\Http\Request;
use Netflex\Commerce\Contracts\Order;
use Netflex\Commerce\Order as OrderAlias;

abstract class BaseEnsurePaymentsAreSyncedMiddleware
{
    abstract function resolveOrder(?Request $request): ?Order;

    public function handle(Request $request, Closure $next)
    {

        $order = $this->resolveOrder($request);
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

            if ($refresh) {
                $order->refreshOrder();

                if ($order->canBeCompleted()) {
                    $order->completeOrder();
                    $order->refreshOrder();
                    return \Apility\Payment\Routing\Payment::route('receipt', ['order' => $order]);
                }
            }
        }

        return $next($request);
    }
}
