<?php

namespace Apility\Payment\Http\Controllers;

use Illuminate\Routing\Controller;

use Apility\Payment\Contracts\PaymentController as PaymentControllerContract;

use Apility\Payment\Routing\Payment as Router;
use Apility\Payment\Jobs\SendReceipt;
use Apility\Payment\Payments\NullPayment;
use Apility\Payment\Requests\PaymentCallbackRequest;
use Apility\Payment\Requests\PaymentRequest;
use Netflex\Commerce\Order;
use Netflex\Render\PDF;

class PaymentController extends Controller implements PaymentControllerContract
{
    public function pay(PaymentRequest $request)
    {
        return $request->getPayment()
            ->pay();
    }

    public function callback(PaymentCallbackRequest $request)
    {
        $payment = $request->getPayment();
        $order = $request->getOrder();
        $processor = $payment->getProcessor();

        $order->addLogInfo('[' . $processor->getProcessor() . ']: Payment callback received with id: ' . $payment->getPaymentId());

        if (!$payment->paid()) {
            $order->addLogInfo('[' . $processor->getProcessor() . ']: Charging payment with id ' . $payment->getPaymentId());
            if (!$payment->charge()) {
                $order->addLogDanger('[' . $processor->getProcessor() . ']: Payment with id ' . $payment->getPaymentId() . ' failed to charge');
                $payment->cancel();
                $payment = null;
            } else {
                $order->addLogSuccess('[' . $processor->getProcessor() . ']: Payment with id ' . $payment->getPaymentId() . ' charged successfully');
            }
        } else {
            $order->addLogSuccess('[' . $processor->getProcessor() . ']: Payment with id ' . $payment->getPaymentId() . ' already charged');
        }

        if ($order->getTotalPaid() < $payment->getTotalAmount()) {
            if (!($payment instanceof NullPayment)) {
                $order->registerPayment($payment);
                $order->addLogSuccess('[' . $processor->getProcessor() . ']: Payment with id ' . $payment->getPaymentId() . ' registered with transaction ' . $payment->getTransactionId());
            }

            $order->checkoutOrder();
            $order->registerOrder();
            $order->lockOrder();
            $order->refreshOrder();

            dispatch(new SendReceipt($order));
        }

        return redirect()->to(Router::route('receipt', ['order' => $order]));
    }

    public function receipt(Order $order)
    {
        return view('payment::receipt', [
            'order' => $order
        ]);
    }

    public function receiptPdf(Order $order)
    {
        return PDF::view('payment::pdf', ['order' => $order])
            ->format(PDF::FORMAT_A4)
            ->emulatedMedia('screen')
            ->download();
    }
}
