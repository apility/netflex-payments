<?php

namespace Apility\Payment\Facades;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Contracts\PaymentProcessor;
use Apility\Payment\Processors\NullProcessor;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\App;
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

    public static function processor(string $processor): ?PaymentProcessor
    {
        $processor = App::make('payment.processors.' . $processor);

        return new class($processor) implements PaymentProcessor
        {
            protected PaymentProcessor $processor;

            public function __construct(PaymentProcessor $processor)
            {
                $this->processor = $processor;
            }

            public function getProcessor(): string
            {
                return $this->processor->getProcessor();
            }

            public function setup(string $driver, array $config)
            {
                $this->processor->setup($driver, $config);
            }

            public function create(Order $order): PaymentContract
            {
                $payment = $this->processor->create($order);
                $order->setOrderData('paymentId', $payment->getPaymentId());
                $order->setOrderData('paymentProcessor', $this->getProcessor());

                return $payment;
            }

            public function find($paymentId): ?PaymentContract
            {
                return $this->processor->find($paymentId);
            }
        };
    }

    public static function create(Order $order, ?PaymentProcessor $processor = null): PaymentContract
    {
        if (!count($order->getOrderCartItems()) || !$order->getOrderTotal() > 0) {
            $processor = new NullProcessor;
        } else {
            /** @var PaymentProcessor */
            $processor = $processor ?? static::getFacadeRoot();
        }

        $payment = $processor->create($order);

        $order->setOrderData('paymentId', $payment->getPaymentId());
        $order->setOrderData('paymentProcessor', $processor->getProcessor());

        return $payment;
    }
}
