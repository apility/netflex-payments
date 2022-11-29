<?php

namespace Apility\Payment\Facades;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Contracts\PaymentProcessor;
use Apility\Payment\Processors\AbstractProcessor;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\App;
use Netflex\Commerce\Contracts\Order;
use Netflex\Commerce\Contracts\Payment as CommercePayment;

class Payment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payment.processor';
    }

    public static function resolve(CommercePayment $payment): ?PaymentContract
    {
        if ($processor = static::processor($payment->getPaymentMethod())) {
            return $processor->find($payment->getTransactionId());
        }

        return null;
    }

    public static function cancelPendingPayments(Order $order): int
    {
        return collect($order->getOrderPayments())
            ->map(fn(CommercePayment $pay) => Payment::resolve($pay))
            ->filter()
            ->reject(fn(PaymentContract $pay) => $pay->isLocked())
            ->each(function (PaymentContract $pay) use ($order) {
                $pay->cancel();
                $order->updatePayment($pay);
                return $pay;
            })
            ->count();
    }

    public static function processor(string $processor): ?PaymentProcessor
    {
        $processor = App::make('payment.processors.' . $processor);

        return new class($processor) extends AbstractProcessor
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
        if ($order->getOrderTotal() == 0) {
            $processor = static::processor('free');
        } else {
            $processor = $processor ?? static::getFacadeRoot();
        }
        /** @var PaymentProcessor $processor */

        $payment = $processor->create($order);

        $order->setOrderData('paymentId', $payment->getPaymentId());
        $order->setOrderData('paymentProcessor', $processor->getProcessor());

        return $payment;
    }
}
