<?php

namespace Apility\Payment\Providers;

use Apility\Payment\Contracts\PaymentProcessor;
use Apility\Payment\Processors\NullProcessor;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Blade;

class PaymentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../../config/payment.php' => $this->app->configPath('payment.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../../../resources/views' => $this->app->resourcePath('views/vendor/payment'),
        ], 'views');

        $this->loadViewsFrom(__DIR__ . '/../../../resources/views', 'payment');
        $this->mergeConfigFrom(__DIR__ . '/../../../config/payment.php', 'payment');
    }

    public function register()
    {
        Blade::directive('price', function ($price) {
            return '<?php echo(format_price(' . $price . ')); ?>';
        });

        Blade::directive('date', function ($date) {
            return '<?php echo(\Illuminate\Support\Carbon::parse(' . $date . ')->format(__("dateformat"))); ?>';
        });

        Blade::directive('time', function ($date) {
            return '<?php echo(\Illuminate\Support\Carbon::parse(' . $date . ')->format(__("timeformat"))); ?>';
        });

        Blade::directive('datetime', function ($date) {
            return '<?php echo(\Illuminate\Support\Carbon::parse(' . $date . ')->format(__("dateformat"))); ?> <?php echo __("at"); ?> <?php echo(\Illuminate\Support\Carbon::parse(' . $date . ')->format(__("timeformat"))); ?>';
        });


        $this->booted(function () {
            $this->app->bind('payment.receipt', Config::get('payment.receipt'));
            $this->app->bind('payment.jobs.order.process', Config::get('payment.process_order_job'));

            foreach (Config::get('payment.processors', []) as $alias => $config) {
                $driver = $config['driver'] ?? null;
                unset($config['driver']);

                if ($driver) {
                    $this->app->bind('payment.processors.' . $alias, function () use ($alias, $driver, $config) {
                        /** @var PaymentProcessor */
                        $processor = new $driver();
                        $processor->setup($alias, $config);
                        return $processor;
                    });
                }
            }
        });

        $this->app->bind('payment.controller', fn () => Config::get('payment.controller'));
        $this->app->bind('payment.processor', fn () => $this->app->make('payment.processors.' . Config::get('payment.default')));

        $this->loadViewComponentsAs('payment', [
            \Apility\Payment\View\Components\Receipt::class,
            \Apility\Payment\View\Components\QrCode::class,
        ]);
    }
}
