<?php

namespace Apility\Payment\View\Components;

use chillerlan\QRCode\QRCode;

use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

use Netflex\Commerce\Contracts\Order;

class Receipt extends Component
{
    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function qrCodeString(): ?string
    {
        if ($qrCode = $this->order->getOrderData('qrCode')) {
            return $qrCode;
        }

        return null;
    }

    public function qrCodeImage(): ?HtmlString
    {
        if ($qrCode = $this->qrCodeString()) {
            $qr = new QRCode();
            return new HtmlString($qr->render($qrCode));
        }

        return null;
    }

    public function render()
    {
        return view('payment::components.receipt');
    }
}
