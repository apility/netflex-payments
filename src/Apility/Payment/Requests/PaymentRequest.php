<?php

namespace Apility\Payment\Requests;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Contracts\PaymentProcessor;

use Apility\Payment\Routing\Payment as PaymentRouter;
use Apility\Payment\Facades\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

use Netflex\Commerce\Contracts\Order;
use Netflex\Commerce\Contracts\Payment as CommercePayment;
use Netflex\Commerce\Order as OrderModel;

class PaymentRequest extends FormRequest
{
    protected ?Order $order = null;
    protected ?PaymentContract $payment = null;

    public function authorize()
    {
        return $this->getOrder() && !$this->orderAlreadyPaid();
    }

    public function orderAlreadyPaid(): bool
    {
        $order = $this->getOrder();

        if ($order) {
            if ($order->getTotalPaid() > 0) {
                return true;
            }
        }

        $processor = $this->getPaymentProcessor();

        if ($order && $processor) {
            $payment = $processor->find($order->getOrderData('paymentId'));

            if ($payment && $payment->paid()) {
                return true;
            }
        }

        return false;
    }

    public function rules()
    {
        return [];
    }

    public function getPaymentProcessor(): ?PaymentProcessor
    {
        if ($order = $this->getOrder()) {
            if ($processor = $order->getOrderData('paymentProcessor')) {
                if (App::has('payment.processors.' . $processor)) {
                    return App::make('payment.processors.' . $processor);
                }
            }
        }

        return null;
    }

    public function getOrder(): ?Order
    {
        $this->order = $this->order ?? OrderModel::retrieveBySecret($this->route('order'));

        if ($this->order && $this->order->getOrderId()) {
            return $this->order;
        }

        return null;
    }

    public function getPayment(): ?PaymentContract
    {
        if ($this->payment !== null) {
            return $this->payment;
        }

        $payment = null;
        $order = $this->getOrder();

        Payment::cancelPendingPayments($order);

        $payment = $payment ?? Payment::create($order);
        $processor = $payment->getProcessor();

        $order->registerPayment($payment);
        $order->setOrderData('paymentId', $payment->getPaymentId(), 'Payment Id');
        $order->setOrderData('paymentProcessor', $processor->getProcessor(), 'Payment Processor');

        $order->addLogInfo('[' . $processor->getProcessor() . ']: Payment initiated');
        $order->addLogInfo('[' . $processor->getProcessor() . ']: Payment created with id: ' . $payment->getPaymentId());

        $this->payment = $payment;

        return $payment;
    }

    public function failedAuthorization()
    {
        if ($this->orderAlreadyPaid()) {
            $order = $this->getOrder();
            $redirect = PaymentRouter::route('receipt', ['order' => $order]);

            if ($processor = $this->getPaymentProcessor()) {
                $redirect = PaymentRouter::route('callback', [
                    'order' => $this->getOrder(),
                    'processor' => $processor
                ]);
            }

            return redirect()
                ->to($redirect)
                ->send();
        }

        parent::failedAuthorization();
    }
}
