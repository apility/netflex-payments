<?php

namespace Apility\Payment\Emails;

use Apility\Payment\Routing\Payment;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\App;

use Netflex\Commerce\Contracts\Order;
use Netflex\Render\PDF;

class ReceiptEmail extends Mailable
{
    public Order $order;

    public string $primaryColor = '#000000';

    public ?string $logo = null;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->view('payment::emails.receipt', [
            'order' => $this->order,
            'primaryColor' => $this->primaryColor,
            'logo' => $this->logo,
        ])
            ->subject(__('Receipt #:receiptId', ['receiptId' => $this->order->getOrderReceiptId()]))
            ->attach($this->getAttachment(), [
                'as' => __('receipt') . '.pdf',
                'mime' => 'application/pdf',
            ]);
    }

    protected function getAttachment()
    {
        if (App::environment() === 'local') {
            return PDF::view('payment::pdf', ['order' => $this->order])
                ->format(PDF::FORMAT_A4)
                ->emulatedMedia('screen')
                ->link();
        }

        return Payment::route('receipt.pdf', $this->order, true);
    }
}
