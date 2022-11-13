<?php

namespace Bfg\Resource\Traits;

use Bfg\Resource\Controller;
use Bfg\Resource\Exceptions\AttemptToCheckBuilderException;
use Bfg\Resource\Exceptions\PermissionDeniedException;
use Bfg\Resource\Exceptions\UndefinedScopeException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use ReflectionException;
use Throwable;

trait ResourceClassApiTrait
{
    /**
     * @param ...$path
     * @return mixed
     * @throws AttemptToCheckBuilderException
     * @throws PermissionDeniedException
     * @throws UndefinedScopeException
     * @throws ReflectionException
     * @throws Throwable
     */
    public static function scope(...$path): mixed
    {
        $result = Controller::callScopes(
            Controller::sortScopes(implode('/', $path), static::class),
            static::class,
            static::getDefaultResource()
        );

        return $result ? ($result instanceof Collection || $result instanceof LengthAwarePaginator ?
            static::collection($result) : static::make($result)) : [];
    }

    /**
     * @param ...$path
     * @return Collection|object
     * @throws AttemptToCheckBuilderException
     * @throws PermissionDeniedException
     * @throws UndefinedScopeException
     * @throws ReflectionException
     * @throws Throwable
     */
    public static function use(...$path)
    {
        /** @var Collection|static|object|null $use */
        $use = static::scope(...$path)->toFields();
        return isset($use[0]) ? collect($use)->map(fn ($i) => (object) $i) : (object) $use;
    }
}
