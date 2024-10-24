<?php

namespace Apility\Payment\Facades;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Contracts\PaymentProcessor;
use Apility\Payment\Events\PaymentCancelled;
use Apility\Payment\Events\PaymentCreated;
use Apility\Payment\Processors\AbstractProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
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
                event(new PaymentCancelled($order, $pay, $pay->getProcessor()));
                $order->updatePayment($pay);

                return $pay;
            })
            ->count();
    }

    public static function processor(?string $processor = null): ?PaymentProcessor
    {

        $processor = $processor ?: config('payment.default');

        $processor = App::make('payment.processors.' . $processor);

        return new class($processor) extends AbstractProcessor {
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

            public function create(Order $order, array $options = []): PaymentContract
            {
                $payment = $this->processor->create($order, $options);
                $order->setOrderData('paymentId', $payment->getPaymentId());
                $order->setOrderData('paymentProcessor', $this->getProcessor());
                event(new PaymentCreated($order, $payment, $this->processor));
                return $payment;
            }

            public function find($paymentId): ?PaymentContract
            {
                return $this->processor->find($paymentId);
            }

            public function resolve(Request $request): ?PaymentContract
            {
                return $this->processor->resolve($request);
            }
        };
    }

    public static function create(Order $order, ?PaymentProcessor $processor = null, array $options = []): PaymentContract
    {
        if ($order->getOrderTotal() == 0) {
            $processor = static::processor('free');
        } else {
            $processor = $processor ?? static::getFacadeRoot();
        }
        /** @var PaymentProcessor $processor */
        $payment = $processor->create($order, $options);

        $order->setOrderData('paymentId', $payment->getPaymentId());
        $order->setOrderData('paymentProcessor', $processor->getProcessor());

        return $payment;
    }


    public static function find(string $paymentId, ?PaymentProcessor $paymentProcessor): ?PaymentContract
    {
        return ($paymentProcessor ?? static::processor())->find($paymentId);
    }


    public static function cancel(Order $order, string $paymentId, ?PaymentProcessor $paymentProcessor): bool
    {
        if (collect($order->getOrderPayments())->where('transaction_id', '=', $paymentId)->count() == 0) {
            return false;
        }

        $paymentProcessor = $paymentProcessor ?? static::processor();
        $payment = $paymentProcessor->find($paymentId);
        if (!$payment) {
            return false;
        }
        $wasCancelled = $payment->cancel();

        if ($wasCancelled) {
            $payment = $paymentProcessor->find($paymentId);
            event(new PaymentCancelled($order, $payment, $paymentProcessor));
        }

        return $wasCancelled;
    }

}
