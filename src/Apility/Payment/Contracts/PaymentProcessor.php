<?php

namespace Apility\Payment\Contracts;

use Netflex\Commerce\Contracts\Order;
use Illuminate\Contracts\Routing\UrlRoutable;

interface PaymentProcessor extends UrlRoutable
{
    public function getProcessor(): string;

    public function setup(string $driver, array $config);

    public function create(Order $order): Payment;

    public function find($paymentId): ?Payment;

    /* public function charge(Payment $payment): Payment; */
}
