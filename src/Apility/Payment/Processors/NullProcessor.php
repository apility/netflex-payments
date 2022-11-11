<?php

namespace Apility\Payment\Processors;

use Apility\Payment\Contracts\Payment;
use Apility\Payment\Payments\NullPayment;

use Netflex\Commerce\Contracts\Order;

class NullProcessor extends AbstractProcessor
{
    public function getProcessor(): string
    {
        return 'null';
    }

    public function create(Order $order): Payment
    {
        return new NullPayment($this, $order);
    }

    public function find($paymentId = null): ?Payment
    {
        return new NullPayment($this);
    }
}
