<?php

namespace Apility\Payment\Http\Controllers;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Contracts\PaymentProcessor;
use Apility\Payment\Facades\Payment;
use Apility\Payment\Processors\NetsEasy;
use Exception;
use Illuminate\Routing\Controller;

use Apility\Payment\Contracts\PaymentController as PaymentControllerContract;

use Apility\Payment\Routing\Payment as Router;
use Apility\Payment\Jobs\SendReceipt;
use Apility\Payment\Requests\PaymentCallbackRequest;
use Apility\Payment\Requests\PaymentRequest;
use Netflex\Commerce\Contracts\Order as OrderContract;
use Netflex\Commerce\Order;
use Netflex\Render\PDF;

class PaymentController extends Controller implements PaymentControllerContract
{
    /**
     * @throws Exception
     */
    public function pay(PaymentRequest $request)
    {
        $order = $request->getOrder();
        $processor = $request->getPaymentProcessor();

        /** @var Order $order */
        return $this->createNewPayment($order, $processor)
            ->pay();
    }

    public function callback(PaymentCallbackRequest $request)
    {
        $order = $request->getOrder();

        if (!$order->isLocked()) {
            $payment = $request->getPayment();

            if (!$payment) {
                return redirect()->to(Router::route('pay', ['order' => $order]));
            }

            if (!$payment->paid()) {
                if (!$payment->charge()) {
                    $payment->cancel();
                }
                $order->updatePayment($payment);
            }

            $order->refreshOrder();

            if ($order->canBeCompleted()) {
                $order->completeOrder();
                $order->refreshOrder();
            }
        }
        return redirect()->to(Router::route('receipt', ['order' => $order]));
    }

    public function receipt(Order $order)
    {
        $view = 'receipt';

        if (request()->boolean('pdf')) {
            $view = 'pdf';
        }

        if (!$order->isCompleted()) {
            return redirect()->to(Router::route('pay', ['order' => $order]));
        }

        return view('payment::' . $view, [
            'order' => $order
        ]);
    }

    public function receiptPdf(Order $order)
    {
        $pdf = PDF::url(Router::route('receipt', [
            'order' => $order,
            'pdf' => 1
        ]));

        if (app()->environment() === 'local') {
            $pdf = PDF::view('payment::pdf', ['order' => $order]);
        }

        return $pdf->format(PDF::FORMAT_A4)
            ->emulatedMedia('screen')
            ->download();
    }

    /**
     *
     * Creates a new payment
     *
     * @param OrderContract $order
     * @param PaymentProcessor|null $processor
     * @param array{
     *   create_payload_options: array,
     *   checkout_language: string,
     *   country_code: string
     * } $options
     * @return PaymentContract
     */
    protected function createPayment(OrderContract $order, ?PaymentProcessor $processor, array $options)
    {
        return Payment::create($order, $processor, $options);
    }

    /**
     * @throws Exception
     */
    private function createNewPayment(OrderContract $order, PaymentProcessor $processor = null): PaymentContract
    {
        Payment::cancelPendingPayments($order);

        $payment = $this->createPayment($order, $processor, []);
        $processor = $payment->getProcessor();

        $order->registerPayment($payment);
        $order->setOrderData('paymentId', $payment->getPaymentId(), 'Payment Id');
        $order->setOrderData('paymentProcessor', $processor->getProcessor(), 'Payment Processor');
        $order->addLogInfo('[' . $processor->getProcessor() . ']: Payment initiated');
        $order->addLogInfo('[' . $processor->getProcessor() . ']: Payment created with id: ' . $payment->getPaymentId());

        return $payment;
    }

}
