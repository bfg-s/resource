<?php

namespace Bfg\Resource\Attributes;

use Attribute;
use Bfg\Route\Attributes\Invokable;
use Illuminate\Routing\Router;

/**
 * Class CanScope.
 * @package Bfg\Resource\Attributes
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class CanScope
{
    /**
     * @param  string|null  $permission
     */
    public function __construct(
        public ?string $permission = null,
    ) {
    }
}
