<?php

namespace Bfg\Resource\Attributes;

use Attribute;
use Bfg\Route\Attributes\Invokable;
use Illuminate\Routing\Router;

/**
 * Class GetResource.
 * @package Bfg\Resource\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
class GetResource extends Invokable
{
    /**
     * Invokable constructor.
     * @param  string  $uri
     * @param  string|null  $name
     * @param  array|string  $middleware
     */
    public function __construct(
        string $uri = '[class_name]',
        ?string $name = null,
        array|string $middleware = [],
    ) {
        parent::__construct(
            uri: "{$uri}/{scope?}",
            method: Router::$verbs,
            name: $name,
            responsible: 'routeAction',
            middleware: $middleware,
            where: ['scope', "^[a-zA-Z0-9\\_\\/\\-\*\=\!\>\<]*$"]
        );
    }

    /**
     * @param  string  $class
     * @return string
     */
    public function class_replacer(string $class): string
    {
        return str_replace('_resource', '', $class);
    }
}
