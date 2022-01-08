<?php

namespace Bfg\Resource;

use Bfg\Resource\Commands\ResourceMakeCommand;
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
     * @throws \ReflectionException
     */
    public function register()
    {
        $this->app->extend('command.resource.make', function ($app) {
            return new ResourceMakeCommand(app('files'));
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
