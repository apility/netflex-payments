<?php

namespace Apility\Payment\Processors;

use Exception;
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
     * @param string $driver
     * @param array{
     *     complete_payment_button_text: string,
     *     checkout_language: string,
     *     country_code: string
     * } $config
     * @return void
     */
    public function setup(string $driver, array $config)
    {
        $this->processor = $driver;
        $config['mode'] = $config['mode'] ?? Str::startsWith($config['secret_key'] ?? '', 'test') ? 'test' : 'live';

        Easy::setup($config);
        $this->completePaymentButtonText = $config['complete_payment_button_text'] ?? 'pay';
        $this->checkoutLanguage = $config['checkout_language'] ?? null;
        $this->countryCode = $config['country_code'] ?? null;
    }


    /**
     * @param Order $order
     * @param array $options
     * @return Payment
     * @throws Exception
     */
    public function create(Order $order, array $options): Payment
    {
        $options['complete_payment_button_text'] = $options['complete_payment_button_text'] ?? $this->completePaymentButtonText;
        $options['country_code'] = $options['country_code'] ?? $this->countryCode;
        $options['checkout_language'] = $options['checkout_language'] ?? $this->checkoutLanguage;

        if (!count($order->getOrderCartItems()) || !$order->getOrderTotal() > 0) {
            throw new Exception('You are trying to make a Nets payment for a free order. Use the FreeProcessor for free orders, as Nets does not allow us to create payments of 0 kr');
        }

        return NetsEasyPayment::make($this, $order, $options);
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
