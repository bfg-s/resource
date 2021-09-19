<?php

namespace Bfg\Resource;

use Bfg\Installer\Providers\InstalledProvider;
use Bfg\Resource\Commands\ResourceMakeCommand;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\SanctumServiceProvider;

/**
 * Class ServiceProvider.
 * @package Bfg\Resource
 */
class ServiceProvider extends InstalledProvider
{
    /**
     * Set as installed by default.
     * @var bool
     */
    public bool $installed = true;

    /**
     * Executed when the provider is registered
     * and the extension is installed.
     * @return void
     */
    public function installed(): void
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

    /**
     * Executed when the provider run method
     * "boot" and the extension is installed.
     * @return void
     */
    public function run(): void
    {
        //
    }
}
