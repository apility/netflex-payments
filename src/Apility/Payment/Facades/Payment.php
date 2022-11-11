<?php

namespace Apility\Payment\Facades;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Contracts\PaymentProcessor;
use Apility\Payment\Processors\NullProcessor;


use Illuminate\Support\Facades\Facade;
use Netflex\Commerce\Contracts\Order;

/**
 * @method static \Apility\Payment\Contracts\Payment create(\Netflex\Commerce\Contracts\Order $order)
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payment.processor';
    }

    public static function create(Order $order): PaymentContract
    {
        /** @var ?PaymentProcessor */
        $processor = null;

        if (!count($order->getOrderCartItems()) || !$order->getOrderTotal() > 0) {
            $processor = new NullProcessor;
        } else {
            /** @var PaymentProcessor */
            $processor = static::getFacadeRoot();
        }

        $payment = $processor->create($order);

        $order->setOrderData('paymentId', $payment->getPaymentId());
        $order->setOrderData('paymentProcessor', $processor->getProcessor());

        return $payment;
    }
}
