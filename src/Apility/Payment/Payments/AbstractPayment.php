<?php

namespace Apility\Payment\Payments;

use DateTimeInterface;
use Apility\Payment\Contracts\Payment;

abstract class AbstractPayment implements Payment
{
    public function getPaymentMethod(): string
    {
        $processor = $this->getProcessor();
        return $processor->getProcessor();
    }

    public function getPaymentStatus(): string
    {
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
        return $this->getChargedAmount();
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

    public function toResponse($request)
    {
        return $this->pay();
    }
}
