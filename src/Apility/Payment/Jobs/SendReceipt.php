<?php

namespace Apility\Payment\Jobs;

use Apility\Payment\Emails\ReceiptEmail;
use Netflex\Commerce\Contracts\Order;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class SendReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Order $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (App::has('payment.receipt')) {
            $locale = App::getLocale();

            App::setLocale($this->order->getOrderData('locale'), $locale);

            /** @var Mailable */
            $receipt = App::make('payment.receipt', ['order' => $this->order]);

            Mail::to($this->order->getOrderCustomerEmail())
                ->send($receipt);

            $this->order->addLogInfo('Receipt sent to ' . $this->order->getOrderCustomerEmail());

            App::setLocale($locale);
        }
    }
}
