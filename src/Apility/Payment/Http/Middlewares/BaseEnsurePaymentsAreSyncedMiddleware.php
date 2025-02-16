<?php

namespace Apility\Payment\Http\Middlewares;

use Apility\Payment\Events\OrderCompleted;
use Apility\Payment\Facades\Payment;
use Apility\Payment\Facades\Router;
use Closure;
use Illuminate\Http\Request;
use Netflex\Commerce\Contracts\Order;

abstract class BaseEnsurePaymentsAreSyncedMiddleware
{
    abstract function resolveOrder(?Request $request): ?Order;

    public function handle(Request $request, Closure $next)
    {

        $order = $this->resolveOrder($request);

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

            if ($refresh) {
                $order->refreshOrder();

                if ($order->canBeCompleted()) {
                    $order->completeOrder();
                    $order->refreshOrder();
                    event(new OrderCompleted($order));
                    return redirect(Router::route('receipt', ['order' => $order], false));
                }
            }
        }

        return $next($request);
    }
}
