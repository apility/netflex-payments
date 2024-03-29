<?php

namespace Apility\Payment\Requests;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Contracts\PaymentProcessor;

use Apility\Payment\Facades\Router;
use Apility\Payment\Facades\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

use Netflex\Commerce\Contracts\Order;
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
            if ($order->getTotalPaid() >= $order->getOrderTotal()) {
                return true;
            }
        }

        $processor = $this->getPaymentProcessor();

        if ($order && $processor) {
            if ($paymentId = $order->getOrderData('paymentId')) {
                $payment = $processor->find($paymentId);

                if ($payment && $payment->paid()) {
                    return true;
                }
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

    public function getPayment(?PaymentProcessor $processor = null): ?PaymentContract
    {
        throw new \Exception('This functionality has been moved to the PaymentController');
    }

    public function failedAuthorization()
    {
        if ($this->orderAlreadyPaid()) {
            $order = $this->getOrder();
            $redirect = Router::route('receipt', ['order' => $order]);

            if ($processor = $this->getPaymentProcessor()) {
                $redirect = Router::route('callback', [
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
