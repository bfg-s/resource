<?php

namespace Bfg\Resource\Traits\Model;

use Illuminate\Database\Eloquent\Model;

trait ModelOnlyScopeTrait
{
    /**
     * Set only for result models scope.
     *
     * @param Model $model
     * @param  array  $fields
     * @return mixed
     */
    public static function onlyScope($model, array $fields): mixed
    {
        return $model?->only($fields);
    }
}
