<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Tests;

use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\TestCase as Orchestra;
use SammyBoy\Reporting\ReportingServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Uzapoint\EventBus\EventBusServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            EventBusServiceProvider::class,
            ReportingServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app->make(Repository::class);

        $config->set('app.name', 'sales-ms');
        $config->set('reporting.exchange', 'sales.events');
        $config->set('database.default', 'testing');
    }
}
