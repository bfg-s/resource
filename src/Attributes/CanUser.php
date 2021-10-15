<?php

namespace Bfg\Resource\Attributes;

use Attribute;
use Bfg\Route\Attributes\Invokable;
use Illuminate\Routing\Router;

/**
 * Class CanUser.
 * @package Bfg\Resource\Attributes
 */
#[Attribute(
    Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE
)] class CanUser
{
    /**
     * @param  string  $local_field
     * @param  string  $user_field
     */
    public function __construct(
        public string $local_field = 'user_id',
        public string $user_field = 'id',
    ) {
    }
}
