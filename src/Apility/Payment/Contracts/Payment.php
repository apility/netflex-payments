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

    /**
     *
     * Refund payment partially or fully.
     *
     * Amount must be supplied in the same format as getChargedAmount/getTotalAmount.
     * In other words, dont use cents, use the main currency unit(NOK, USD), not the fractional currency unit(Øre, Cents)
     *
     * @see self::getChargedAmount
     * @see self::getTotalAmount
     *
     * @param float|null $amount
     * @return bool
     */
    public function refund(?float $amount = null): bool;

    public function paid(): bool;

    public function getPaymentType(): string;

    public function getChargedAmount(): float;

    public function getReservedAmount(): float;

    public function getTotalAmount(): float;

    /** @return int|string */
    public function getPaymentId();

    public function getPaymentUrl(): string;

    public function setOptions(array $options);
}
