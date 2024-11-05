<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Kafka\KafkaConnector;

class KafkaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $manager = $this->app['queue'];
        $manager->addConnector('kafka', function() {
            return new KafkaConnector();
        });
    }
}
