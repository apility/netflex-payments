<?php

namespace Apility\Payment\Events;

use Apility\Payment\Contracts\Payment;
use Apility\Payment\Contracts\PaymentProcessor;
use Netflex\Commerce\Contracts\Order;

class PaymentEvent
{
    public Order $order;
    public Payment $payment;
    public PaymentProcessor $paymentProcessor;

    public function __construct(
        Order            $order,
        Payment          $payment,
        PaymentProcessor $paymentProcessor
    )
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->paymentProcessor = $paymentProcessor;
    }
}