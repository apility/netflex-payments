<?php

namespace Apility\Payment\Payments;

use DateTimeInterface;

use Apility\Payment\Contracts\PaymentProcessor;
use Apility\Payment\Routing\Payment as PaymentRouter;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

use Netflex\Commerce\Contracts\Order;

class NullPayment extends AbstractPayment
{
    protected ?Order $order;
    protected PaymentProcessor $processor;
    private string $transactionId;

    public function __construct(PaymentProcessor $processor, ?Order $order = null, ?string $transactionId = null)
    {
        $this->processor = $processor;
        $this->order = $order;
        $this->transactionId = $transactionId ?? $order ? $order->getOrderSecret() : '';
    }

    public function getProcessor(): PaymentProcessor
    {
        return $this->processor;
    }

    public function pay(): RedirectResponse
    {
        return redirect($this->getPaymentUrl());
    }

    public function charge(): bool
    {
        return true;
    }

    public function cancel(): bool
    {
        return true;
    }

    public function refund(?float $amount = null): bool
    {
        return true;
    }

    public function getPaymentType(): string
    {
        return '';
    }

    public function getPaymentMethod(): string
    {
        return 'free';
    }

    public function isCharged(): bool
    {
        return true;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getChargedAmount(): float
    {
        return 0.0;
    }

    public function getPaymentDate(): DateTimeInterface
    {
        return Carbon::now();
    }

    public function paid(): bool
    {
        return true;
    }

    public function getTotalAmount(): float
    {
        return 0.0;
    }

    /** @return int|string */
    public function getPaymentId()
    {
        return $this->transactionId;
    }

    public function getPaymentUrl(): string
    {
        return PaymentRouter::callback($this->order, $this);
    }

    function cancelled(): bool
    {
        return false;
    }

    public function isLocked(): bool
    {
        return true;
    }

    public function getIsPending(): bool
    {
        return false;
    }

    public function getReservedAmount(): float
    {
        return $this->getTotalAmount();
    }
}
