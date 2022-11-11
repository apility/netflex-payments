<?php

namespace Apility\Payment\Contracts;

use Apility\Payment\Requests\PaymentRequest;
use Apility\Payment\Requests\PaymentCallbackRequest;

use Netflex\Commerce\Order;

interface PaymentController
{
    public function pay(PaymentRequest $request);
    public function callback(PaymentCallbackRequest $request);
    public function receipt(Order $order);
}
