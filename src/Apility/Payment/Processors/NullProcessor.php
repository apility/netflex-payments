<?php

namespace Apility\Payment\Processors;

use Apility\Payment\Contracts\Payment;
use Apility\Payment\Payments\NullPayment;

use Netflex\Commerce\Contracts\Order;

class NullProcessor extends AbstractProcessor
{
    public function getProcessor(): string
    {
        return 'free';
    }

    public function create(Order $order, array $options): Payment
    {
        return new NullPayment($this, $order);
    }

    public function find($paymentId = null): ?Payment
    {
        return new NullPayment($this, null, $paymentId);
    }

    public function resolve($request): ?Payment
    {
        return new NullPayment($this);
    }
}
