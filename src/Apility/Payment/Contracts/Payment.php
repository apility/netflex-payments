<?php

namespace Apility\Payment\Contracts;

use Illuminate\Http\RedirectResponse;
use Netflex\Commerce\Contracts\Payment as OrderPayment;
use Illuminate\Contracts\Support\Responsable;

interface Payment extends OrderPayment, Responsable
{
    public function getProcessor(): PaymentProcessor;

    public function pay(): RedirectResponse;

    public function charge(): bool;
    public function cancel(): bool;
    public function refund(): bool;

    public function paid(): bool;

    public function getPaymentType(): string;

    public function getChargedAmount(): float;

    public function getTotalAmount(): float;

    /** @return int|string */
    public function getPaymentId();

    public function getPaymentUrl(): string;
}
