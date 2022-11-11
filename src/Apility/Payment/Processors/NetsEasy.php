<?php

namespace Apility\Payment\Processors;

use Illuminate\Support\Str;

use Nets\Easy;

use Apility\Payment\Contracts\Payment;
use Apility\Payment\Payments\NetsEasyPayment;

use Netflex\Commerce\Contracts\Order;

class NetsEasy extends AbstractProcessor
{
    protected string $processor = 'nets-easy';
    protected string $completePaymentButtonText = 'pay';

    public function getProcessor(): string
    {
        return $this->processor;
    }

    public function setup(string $processor, array $config)
    {
        $this->processor = $processor;
        $config['mode'] = $config['mode'] ?? Str::startsWith($config['secret_key'] ?? '', 'test') ? 'test' : 'live';

        Easy::setup($config);
        $this->completePaymentButtonText = $config['complete_payment_button_text'] ?? 'pay';
    }

    public function create(Order $order): Payment
    {
        if (!count($order->getOrderCartItems()) || !$order->getOrderTotal() > 0) {
            return parent::create($order);
        }

        return NetsEasyPayment::make($this, $order, $this->completePaymentButtonText);
    }

    public function find($paymentId): ?Payment
    {
        return NetsEasyPayment::find($this, $paymentId);
    }
}
