<?php

namespace Bfg\Resource\Attributes;

use Attribute;
use Bfg\Route\Attributes\Invokable;
use Illuminate\Routing\Router;

/**
 * Class CanFields.
 * @package Bfg\Resource\Attributes
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class CanFields
{
    public array $fields = [];

    /**
     * @param $field
     * @param  mixed  ...$fields
     */
    public function __construct($field = null, ...$fields) {
        $fields = is_array($field) ? $field : ($field ? [$field, ...$fields] : []);
        foreach ($fields as $key => $field) {
            if (is_numeric($key)) $this->fields[$field] = null;
            else $this->fields[$key] = $field;
        }
    }
}
