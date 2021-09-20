<?php

namespace Bfg\Resource\Traits;

use Bfg\Resource\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait ResourceClassApiTrait
{
    /**
     * @param ...$path
     * @return mixed
     */
    public static function scope(...$path)
    {

        $result = Controller::callScopes(
            Controller::sortScopes(implode('/', $path), static::class),
            static::class,
            static::getDefaultResource()
        );

        return $result ? ($result instanceof Collection || $result instanceof LengthAwarePaginator ?
            static::collection($result) : static::make($result)) : [];
    }
}
