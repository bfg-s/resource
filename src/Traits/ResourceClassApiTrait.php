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
     * @throws \Bfg\Resource\Exceptions\AttemptToCheckBuilderException
     * @throws \Bfg\Resource\Exceptions\PermissionDeniedException
     * @throws \Bfg\Resource\Exceptions\UndefinedScopeException
     * @throws \ReflectionException
     * @throws \Throwable
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
