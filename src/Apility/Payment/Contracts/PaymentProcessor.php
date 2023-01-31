<?php

namespace Apility\Payment\Contracts;

use Netflex\Commerce\Contracts\Order;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;

interface PaymentProcessor extends UrlRoutable
{
    public function getProcessor(): string;

    public function setup(string $driver, array $config);

    public function create(Order $order, array $options): Payment;

    public function find($paymentId): ?Payment;

    public function resolve(Request $request): ?Payment;
}
