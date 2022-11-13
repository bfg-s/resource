<?php

namespace Bfg\Resource;

use Bfg\Resource\Commands\BfgRouteListCommand;
use Bfg\Resource\Commands\BfgResourceMakeCommand;
use Illuminate\Foundation\Console\ResourceMakeCommand;
use Illuminate\Foundation\Console\RouteListCommand;
use Laravel\Sanctum\SanctumServiceProvider;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

/**
 * Class ServiceProvider.
 * @package Bfg\Resource
 */
class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register route settings.
     * @return void
     */
    public function register(): void
    {
        $this->app->extend('command.resource.make', function ($app) {
            return new BfgResourceMakeCommand(app('files'));
        });
        $this->app->extend(ResourceMakeCommand::class, function ($app) {
            return new BfgResourceMakeCommand(app('files'));
        });
        $this->app->extend(RouteListCommand::class, function ($app) {
            return new BfgRouteListCommand(app('router'));
        });

        if (class_exists(SanctumServiceProvider::class)) {
            $this->sanctum();
        }
    }

    protected function sanctum()
    {
        config(['auth.guards.api.driver' => 'sanctum']);
    }
}
