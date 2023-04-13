<?php

namespace Apility\Payment\Payments;

use Apility\Payment\Processors\NetsEasy;
use DateTimeInterface;

use Netflex\Commerce\CartItem;
use Netflex\Commerce\Contracts\Order;

use Nets\Easy\Payment as EasyPayment;

use Apility\Payment\Contracts\Payment;
use Apility\Payment\Concerns\CreatesNetsEasyPayments;
use Apility\Payment\Contracts\PaymentProcessor;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class NetsEasyPayment extends AbstractPayment
{
    use CreatesNetsEasyPayments;

    protected PaymentProcessor $processor;
    protected ?EasyPayment $payment;

    /**
     *
     * Which language code we want to append to checkout url
     *
     * @see NetsEasy
     * @var string|null
     */
    protected ?string $checkoutLanguage;

    protected ?string $countryCode;

    protected ?string $chargeId = null;

    protected bool $charge = true;

    public function __construct(PaymentProcessor $processor, ?EasyPayment $payment = null, array $options)
    {
        $this->processor = $processor;
        $this->payment = $payment;
        $this->setOptions($options);
    }

    public function setOptions(array $options)
    {
        $this->countryCode = $options['country_code'];
        $this->checkoutLanguage = $options['checkout_language'];
        $this->charge = isset($options['charge']) ? (bool) $options['charge'] : true;
    }

    public function getProcessor(): PaymentProcessor
    {
        return $this->processor;
    }

    public function getTransactionId(): string
    {
        return $this->payment->paymentId;
    }

    public function getPaymentDate(): DateTimeInterface
    {
        foreach ($this->payment->charges ?? [] as $charge) {
            return Carbon::parse($charge['created']);
        }

        return Carbon::now();
    }

    public function cancel(): bool
    {
        if ($this->getIsPending()) {
            return $this->payment->terminate();
        } else {
            return $this->payment->cancel([
                'amount' => $this->getTotalAmount() * 100,
            ]);
        }
    }

    public function refund(?float $amount = null): bool
    {

        if (!$amount) {
            $options = [
                'amount' => $this->getChargedAmount() * 100
            ];
        } else {
            $options = [
                'amount' => $amount * 100,
                'orderItems' => [
                    $this->createNetsEasyCartItem(new CartItem([
                        'id' => '-1',
                        'entry_name' => 'Refund',
                        'variant_name' => 'sum',
                        'no_of_entries' => 1,
                        'entry_cost' => $amount,
                        'tax_percent' => 1.0,
                        'entries_cost' => $amount,
                        'entries_total' => $amount,
                    ])),
                ]
            ];
        }

        foreach ($this->payment->charges as $charge) {
            if ($this->payment->refund($charge['chargeId'], $options)) {
                return true;
            }
        }

        return false;
    }

    public function paid(): bool
    {
        if ($this->charge) {
            return $this->getChargedAmount() >= $this->getTotalAmount();
        }

        return $this->getReservedAmount() >= $this->getTotalAmount();
    }

    public function getTotalAmount(): float
    {
        return ((float)$this->payment->orderDetails->amount) / 100;
    }

    public function getChargedAmount(): float
    {
        if (isset($this->payment->summary['chargedAmount'])) {
            return ((float)$this->payment->summary['chargedAmount']) / 100;
        }

        return 0.0;
    }

    public function getReservedAmount(): float
    {
        if (isset($this->payment->summary['reservedAmount'])) {
            return ((float)$this->payment->summary['reservedAmount']) / 100;
        }

        return 0.0;
    }

    public function getPaymentType(): string
    {
        return 'Nets Easy';
    }

    public function getPaymentId()
    {
        return $this->payment->paymentId;
    }

    public function getPaymentUrl(): string
    {
        $url = $this->payment->checkout->url;
        if (!$this->checkoutLanguage) {
            return $url;
        }

        $url .= strpos($url, '?') === false ? '?' : '&';
        $url .= "language={$this->checkoutLanguage}";

        return $url;
    }

    public function pay(): RedirectResponse
    {
        return redirect()->to($this->getPaymentUrl());
    }

    public function charge(): bool
    {
        $remaining = $this->getReservedAmount();

        if ($remaining > 0) {
            $this->chargeId = $this->payment->charge(['amount' => $remaining * 100])['chargeId'];
            $this->payment = EasyPayment::retrieve($this->payment->paymentId);
            return true;
        }

        return false;
    }

    public function getCardType(): ?string
    {
        return $this->payment->paymentDetails->paymentMethod ?? null;
    }

    public function getMaskedCardNumber(): ?string
    {
        return $this->payment->paymentDetails->cardDetails->maskedPan ?? null;
    }

    public function getCardExpiry(): ?DateTimeInterface
    {
        if ($expiryDate = $this->payment->paymentDetails->cardDetails->maskedPan ?? null) {
            $matches = [];
            if (preg_match('/(?<month>[0-9]{2})(?<year>[0-9]{2})/', $expiryDate, $matches)) {
                return Carbon::createFromFormat('ym', $matches['year'] . $matches['month'])->endOfMonth();
            }
        }

        return null;
    }

    public static function make(PaymentProcessor $processor, Order $order, array $options): ?Payment
    {
        $options['charge'] = true;
        if (property_exists($processor, 'charge')) {
            $options['charge'] = $processor->charge;
        }

        $instance = new static($processor, null, $options);
        $instance->payment = $instance->createNetsEasyPayment(
            $order,
            $options['complete_payment_button_text'] ?? 'pay',
            $options,
        );

        return $instance;
    }

    public static function find(PaymentProcessor $processor, string $paymentId, ?string $countryCode = null, ?string $checkoutLanguage = null): ?Payment
    {
        if ($payment = EasyPayment::retrieve($paymentId)) {
            return new static($processor, $payment, [
                'country_code' => $countryCode,
                'checkout_language' => $checkoutLanguage,
            ]);
        }

        return null;
    }

    public function isCancelled(): bool
    {
        return $this->payment->terminated || $this->payment->summary && ($this->payment->summary->cancelledAmount ?? 0) > 0;
    }

    public function isLocked(): bool
    {
        return $this->isCancelled() || $this->getChargedAmount() === $this->getTotalAmount();
    }

    public function getIsPending(): bool
    {
        return !$this->isCancelled() && $this->getChargedAmount() == 0.0 && $this->getReservedAmount() == 0.0;
    }

    function cancelled(): bool
    {
        return $this->isCancelled();
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
        if ($this->getChargedAmount() >= $this->getTotalAmount()) {
            return 'captured';
        }

        if ($this->getReservedAmount() >= $this->getTotalAmount()) {
            return 'reserved';
        }

        return 'pending';
    }
}
