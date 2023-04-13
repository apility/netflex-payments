<?php

namespace Apility\Payment\Payments;

use DateTimeInterface;
use Apility\Payment\Contracts\Payment;

abstract class AbstractPayment implements Payment
{

    abstract function cancelled(): bool;

    public function getPaymentMethod(): string
    {
        $processor = $this->getProcessor();
        return $processor->getProcessor();
    }

    public function getPaymentStatus(): string
    {
        if ($this->cancelled()) {
            return 'cancelled';
        }
        return $this->paid() ? 'paid' : 'pending';
    }

    public function getCaptureStatus(): string
    {
        if ($this->paid()) {
            return 'captured';
        }

        return 'reserved';
    }

    public function getPaymentAmount(): float
    {
        if ($this->getCaptureStatus() === 'captured') {
            return $this->getChargedAmount();
        }

        return $this->getReservedAmount();
    }

    public function getCardType(): ?string
    {
        return '';
    }

    public function getMaskedCardNumber(): ?string
    {
        return '';
    }

    public function getCardExpiry(): ?DateTimeInterface
    {
        return null;
    }

    public function setLocked(bool $isLocked)
    {
    }

    public function toResponse($request)
    {
        return $this->pay();
    }

    public function setOptions(array $options)
    {
        //
    }
}
