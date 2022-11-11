<?php

namespace Apility\Payment\Requests;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Contracts\PaymentProcessor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

use Netflex\Commerce\Contracts\Order;
use Netflex\Commerce\Order as OrderModel;

class PaymentCallbackRequest extends FormRequest
{
    public function authorize()
    {
        return $this->getOrder() !== null && $this->getPayment() !== null && $this->getPaymentProcessor() !== null;
    }

    public function rules()
    {
        return [];
    }

    public function getPaymentProcessor(): ?PaymentProcessor
    {
        if (App::has('payment.processors.' . $this->route('processor'))) {
            return App::make('payment.processors.' . $this->route('processor'));
        }

        return null;
    }

    public function getOrder(): ?Order
    {
        $order = OrderModel::retrieveBySecret($this->route('order'));

        if ($order && $order->getOrderId()) {
            return $order;
        }

        return null;
    }

    public function getPayment(): ?PaymentContract
    {
        $paymentId = $this->get('paymentId', $this->get('paymentid', $this->getOrder()->getOrderData('paymentId')));

        return $this->getPaymentProcessor()
            ->find($paymentId);
    }
}
