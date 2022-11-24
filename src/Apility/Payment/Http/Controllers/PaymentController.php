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

        $order = $request->getOrder();

        if (!$order->isLocked()) {
            $payment = $request->getPayment();

            if(!$payment) {
                return redirect()->to(Router::route('pay', ['order' => $order]));
            }

            if (!$payment->paid()) {
                if (!$payment->charge()) {
                    $payment->cancel();
                }
                $order->updatePayment($payment);
            }

            $order->refreshOrder();
            if ($order->isCompletable() && !$order->isCompleted() && !$order->isLocked()) {
                $order->checkoutOrder();
                $order->registerOrder();
                $order->lockOrder();
                $order->refreshOrder();

                dispatch(new SendReceipt($order));
            }
        }
        return redirect()->to(Router::route('receipt', ['order' => $order]));
    }

    public function receipt(Order $order)
    {

        if(!$order->isCompleted()) {
            return redirect()->to(Router::route('pay', ['order' => $order]));
        }

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
