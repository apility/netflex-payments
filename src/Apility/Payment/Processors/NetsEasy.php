<?php

namespace Apility\Payment\Processors;

use Illuminate\Support\Str;

use Nets\Easy;

use Apility\Payment\Contracts\Payment;
use Apility\Payment\Payments\NetsEasyPayment;
use Illuminate\Http\Request;
use Netflex\Commerce\Contracts\Order;

class NetsEasy extends AbstractProcessor
{
    protected string $processor = 'nets-easy';
    protected string $completePaymentButtonText = 'pay';

    /**
     *
     * If set the a language=<checkoutLanguage> query parameter which is
     * used to set checkout language.
     *
     * Locale codes are in *xx-XX* format, see supplied link for valid localizations
     *
     * @link https://developers.nets.eu/nets-easy/en-EU/api/#localization
     * @var string|null
     */
    protected ?string $checkoutLanguage;

    /**
     *
     * Set nets [checkout.countryCode] parameter when creating requests.
     * This does not change the checkout language, but changes which payment methods are available.
     *
     * @link https://developers.nets.eu/nets-easy/en-EU/docs/customize-content/customize-text-and-language-hosted/#build-display-payment-methods
     * @var string|null
     */
    protected ?string $countryCode;

    public function getProcessor(): string
    {
        return $this->processor;
    }


    /**
     * @param string $processor
     * @param array{
     *     complete_payment_button_text: string,
     *     checkout_language: string,
     *     country_code: string
     * } $config
     * @return void
     */
    public function setup(string $processor, array $config)
    {
        $this->processor = $processor;
        $config['mode'] = $config['mode'] ?? Str::startsWith($config['secret_key'] ?? '', 'test') ? 'test' : 'live';

        Easy::setup($config);
        $this->completePaymentButtonText = $config['complete_payment_button_text'] ?? 'pay';
        $this->checkoutLanguage = $config['checkout_language'] ?? null;
        $this->countryCode = $config['country_code'] ?? null;
    }

    public function create(Order $order): Payment
    {
        if (!count($order->getOrderCartItems()) || !$order->getOrderTotal() > 0) {
            return parent::create($order);
        }

        return NetsEasyPayment::make($this, $order, $this->completePaymentButtonText, $this->countryCode, $this->checkoutLanguage);
    }

    public function find($paymentId): ?Payment
    {
        return NetsEasyPayment::find($this, $paymentId, $this->countryCode, $this->checkoutLanguage);
    }

    public function resolve(Request $request): ?Payment
    {
        if ($paymentId = $request->get('paymentId', $request->get('paymentid'))) {
            return $this->find($paymentId);
        }

        return null;
    }
}
