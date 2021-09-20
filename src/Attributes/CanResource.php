<?php

namespace Bfg\Resource\Attributes;

use Attribute;
use Bfg\Route\Attributes\Invokable;
use Illuminate\Routing\Router;

/**
 * Class CanScope.
 * @package Bfg\Resource\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class CanResource
{
    /**
     * @param  string|null  $permission
     */
    public function __construct(
        public ?string $permission = null,
    ) {
    }
}
