<?php

namespace Apility\Payment\Events;

class OrderCompleted
{
    public \Netflex\Commerce\Contracts\Order $order;

    public function __construct(\Netflex\Commerce\Contracts\Order $order)
    {
        $this->order = $order;
    }
}