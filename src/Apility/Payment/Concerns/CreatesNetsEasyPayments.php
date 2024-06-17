<?php

namespace Apility\Payment\Concerns;

use Apility\Payment\Contracts\Payment as PaymentContract;
use Apility\Payment\Routing\Payment as PaymentRouter;

use Illuminate\Support\Str;

use Nets\Easy\Payment;

use Netflex\Commerce\Contracts\CartItem;
use Netflex\Commerce\Contracts\Order;

trait CreatesNetsEasyPayments
{
    protected function createNetsEasyPayment(Order $order, string $completePaymentButtonText = 'pay', array $options): Payment
    {
        $countryCode = $options['country_code'] ?? $this->countryCode ?? null;
        $netsEasyPaymentConfig = array_replace_recursive(array_filter([
            'checkout' => $this->createNetsEasyCheckoutPayload($order, $completePaymentButtonText, $countryCode),
            'order' => $this->createNetsEasyOrderPayload($order),
            'paymentMethods' => $this->createNetsEasyPaymentMethodsPayload($order),
            'notifications' => $this->createNetsEasyNotificationsPayload($order),
        ]), $options['create_payload_options'] ?? []);

        return Payment::create($netsEasyPaymentConfig);
    }

    protected function createNetsEasyCheckoutPayload(Order $order, string $completePaymentButtonText = 'pay', ?string $countryCode = null): array
    {
        /** @var PaymentContract&CreatesNetsEasyPayments $this */

        $processor = $this->getProcessor();

        $payload = [];

        if ($countryCode) {
            $payload['countryCode'] = $countryCode;
        }

        return array_merge($payload, [
            'integrationType' => 'HostedPaymentPage',
            'returnUrl' => PaymentRouter::route('callback', ['order' => $order, 'processor' => $this->getProcessor()]),
            'termsUrl' => '_____________________________________________',
            'merchantHandlesConsumerData' => true,
            'merchantHandlesShippingCost' => true,
            'charge' => property_exists($this, 'charge') ? $this->charge : true,
            'consumer' => array_filter([
                'email' => $order->getOrderCustomerEmail(),
                'phoneNumber' => $this->getNetsEasyOrderPhoneNumberPayload($order),
                'privatePerson' => [
                    'firstName' => $order->getOrderCustomerFirstname(),
                    'lastName' => $order->getOrderCustomerSurname(),
                ]
            ]),
            'appearance' => [
                'textOptions' => [
                    'completePaymentButtonText' => $completePaymentButtonText
                ]
            ],
        ]);
    }

    protected function getNetsEasyOrderPhoneNumberPayload(Order $order): ?array
    {
        if ($phone = $order->getOrderCustomerPhone()) {
            if (Str::length($phone) === 8) {
                return [
                    'prefix' => '+47',
                    'number' => $phone
                ];
            }
        }

        return null;
    }

    protected function createNetsEasyCartItem(CartItem $item)
    {
        return [
            'reference' => (string)$item->getCartItemLineNumber(),
            'name' => mb_strimwidth($this->getNetsEasyCartItemName($item), 0, 125, '..'),
            'quantity' => $item->getCartItemQuantity(),
            'unit' => 'x',
            'unitPrice' => (int)number_format(floatval($item->getCartItemPrice() / $item->getCartItemTaxRate()), 2, '', ''),
            'taxRate' => (int)number_format(($item->getCartItemTaxRate() - 1) * 10000, 0, '', ''), // Taxrate (e.g 25.0 in this case),
            'taxAmount' => (int)number_format(floatval($item->getCartItemTax()), 2, '', ''), // The total tax amount for this item in cents,
            'grossTotalAmount' => (int)number_format(floatval($item->getCartItemTotal()), 2, '', ''), // Total for this item with tax in cents,
            'netTotalAmount' => (int)number_format(floatval($item->getCartItemSubtotal()), 2, '', ''), // Total for this item without tax in cents
        ];
    }

    protected function createNetsEasyOrderPayload(Order $order, array $options = []): array
    {
        $items = array_map(fn (CartItem $item) => $this->createNetsEasyCartItem($item), $order->getOrderCartItems());

        if ($order->checkout->shipping_total > 0) {
            $items[] = [
                'reference' => 'shipping',
                'name' => __('Shipping'),
                'quantity' => 1,
                'unit' => 'x',
                'unitPrice' => (int)number_format(floatval($order->checkout->shipping_cost), 2, '', ''),
                'taxRate' => (int)number_format(($order->checkout->shipping_total / $order->checkout->shipping_cost - 1) * 10000, 0, '', ''), // Taxrate (e.g 25.0 in this case),
                'taxAmount' => (int)number_format(floatval($order->checkout->shipping_tax), 2, '', ''), // The total tax amount for this item in cents,
                'grossTotalAmount' => (int)number_format(floatval($order->checkout->shipping_total), 2, '', ''), // Total for this item with tax in cents,
                'netTotalAmount' => (int)number_format(floatval($order->checkout->shipping_cost), 2, '', ''), // Total for this item without tax in cents
            ];
        }

        return [
            'currency' => $order->getOrderCurrency(),
            'reference' => $order->getOrderSecret(),
            'amount' => (int)number_format(floatval($order->getOrderTotal()), 2, '', ''),
            'items' => $items
        ];
    }

    protected function getNetsEasyCartItemName(CartItem $item): string
    {
        $name = $item->getCartItemProductName();

        if ($variant = $item->getCartItemVariantName()) {
            $name = "$name ($variant)";
        }

        $name = preg_replace('/[^\x00-\x7FæøåÆØÅ]/', '', $name);

        return $name;
    }

    protected function createNetsEasyPaymentMethodsPayload(Order $order): ?array
    {
        return [];
    }

    protected function createNetsEasyNotificationsPayload(Order $order): ?array
    {
        if (env('APP_ENV') !== 'local') {
            return [
                'webHooks' => [
                    [
                        'eventName' => 'payment.checkout.completed',
                        'url' => PaymentRouter::route('callback', ['order' => $order, 'processor' => $this->getProcessor()]),
                        'authorization' => $order->secret,
                        'headers' => [
                            [
                                'webhook' => 'payment.checkout.completed'
                            ]
                        ]
                    ]
                ]
            ];
        }

        return null;
    }
}
