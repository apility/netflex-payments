<div class="mb-3 d-flex justify-content-between">
    <div class="row">
        <h2>
            @lang('Receipt #:receiptId', ['receiptId' => $order->getOrderReceiptId()])
        </h2>
    </div>

    <x-payment-qr-code :string="$order->data->qrCode" />
</div>

<div class="mb-3">
    <h5>@lang('Customer')</h5>
    <table class="table table-striped">
        <tbody>
            <tr>
                <td class="text-muted" style="text-transform: capitalize">@lang('Name')</td>
                <td>{{ $order->getOrderCustomerFirstname() }} {{ $order->getOrderCustomerSurname() }}</td>
            </tr>
            <tr>
                <td class="text-muted">@lang('E-mail')</td>
                <td>{{ $order->getOrderCustomerEmail() }}</td>
            </tr>
            @if($phone = $order->getOrderCustomerPhone())
                <tr>
                    <td class="text-muted">@lang('Phone')</td>
                    <td>{{ $phone }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<div class="mb-3">
    <h5>@lang('Products')</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th class="text-muted">@lang('Product')</th>
                <th class="text-muted">@lang('QTY')</th>
                @if(!request()->get('hide-cost'))
                    <th class="text-muted">@lang('Price')</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($order->cart->items as $item)
                <tr>
                    <td>
                        {{ $item->getCartItemProductName() }}
                        @if($variant = $item->getCartItemVariantName())
                            @if($variant !== $name)
                                ({{ $variant }})
                            @endif>
                        @endif
                    </td>
                    <td>{{ $item->getCartItemQuantity() }}</td>
                    <td>@price($item->getCartItemTotal(), $order->getOrderCurrency())</td>
                </tr>
            @endforeach
            @if(!request()->get('hide-cost'))
                <tr>
                    <td class="p-3" colspan="3"></td>
                </tr>
                <tr>
                    <td><i>@lang('Subtotal')</i></td>
                    <td></td>
                    <td><i>@price($order->getOrderSubtotal(), $order->getOrderCurrency())</i></td>
                </tr>
                <tr>
                    <td><i>@lang('Tax')</i></td>
                    <td></td>
                    <td><i>@price($order->getOrderTax(), $order->getOrderCurrency())</i></td>
                </tr>
                <tr>
                    <td><strong>@lang('Total')</strong></td>
                    <td></td>
                    <td><strong>@price($order->getOrderTotal(), $order->getOrderCurrency())</strong></td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<div class="mt-2">
    <h4>@lang('Total amount'): <strong>@price($order->order_total)</strong></h4>
    @if($paymentMethods = $order->getPaymentMethod())
        <p>
            @lang('Paid with :method :date', [
                'method' => '<strong>' . strtoupper($paymentMethods) . '</strong>',
                'date' => \Illuminate\Support\Carbon::parse($order->checkout->checkout_end)->format(__('dateformat') . ' ' . __('timeformat'))
            ])
        </p>
    @endif

    <div class="mt-3">
        {{ $slot }}
    </div>
</div>