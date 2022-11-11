<?php

namespace Apility\Payment\Processors;

use Apility\Payment\Contracts\PaymentProcessor;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Facades\App;

abstract class AbstractProcessor implements PaymentProcessor, UrlRoutable
{
    public function setup(string $driver, array $config)
    {
        //
    }

    /**
     * Get the value of the model's route key.
     *
     * @return mixed
     */
    public function getRouteKey()
    {
        return $this->getProcessor();
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return '';
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return PaymentProcessor|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return App::make('payment.processors.' . $value);
    }

    /**
     * Retrieve the child model for a bound value.
     *
     * @param  string  $childType
     * @param  mixed  $value
     * @param  string|null  $field
     * @return null
     */
    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return null;
    }

    public function __toString()
    {
        return $this->getProcessor();
    }
}
