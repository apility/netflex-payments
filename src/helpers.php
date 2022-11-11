<?php

if (!function_exists('format_price')) {
    function format_price($price, ?string $currency = null)
    {
        $decimalSeparator = __('decimal_separator');
        $decimalSeparator = $decimalSeparator ? $decimalSeparator : '.';
        $thousandsSeparator = __('thousands_separator');
        $thousandsSeparator = $thousandsSeparator ? $thousandsSeparator : ' ';
        $cent_symbol = __('cent_symbol');
        $cent_symbol = $cent_symbol ? $cent_symbol : '';

        $formattedPrice = number_format($price, 0, $decimalSeparator, $thousandsSeparator) . $cent_symbol;

        if (strpos($price, '.') !== false) {
            $formattedPrice = number_format($price, 2, $decimalSeparator, $thousandsSeparator);
        }

        if ($currency) {
            return __(':currency :price', ['currency' => __($currency ?? 'NOK'), 'price' => $formattedPrice]);
        }

        return $formattedPrice;
    }
}
