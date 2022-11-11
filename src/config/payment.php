<?php

return [
    'default' => env('PAYMENT_PROCESSORS', 'nets-easy'),

    'controller' => \Apility\Payment\Http\Controllers\PaymentController::class,
    'receipt' => \Apility\Payment\Emails\ReceiptEmail::class,

    'process_order_job' => null,

    'processors' => [

        'nets-easy' => [
            'driver' => \Apility\Payment\Processors\NetsEasy::class,
            'invoice' => false,
            'merchant_id' => env('NETS_EASY_MERCHANT_ID'),
            'secret_key' => env('NETS_EASY_SECRET_KEY'),
            'checkout_key' => env('NETS_EASY_CHECKOUT_KEY'),
            'complete_payment_button_text' => 'pay'
        ]
    ],
];
