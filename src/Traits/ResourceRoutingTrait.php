<?php

namespace Bfg\Resource\Traits;

use Bfg\Resource\BfgResource;
use Bfg\Resource\Controller;

trait ResourceRoutingTrait
{
    /**
     * For route api.
     * @return BfgResource|\Illuminate\Contracts\Foundation\Application|mixed
     * @throws \Throwable
     */
    public static function routeAction()
    {
        return app(Controller::class)->index(static::class);
    }
}
