<?php

namespace Bfg\Resource\Traits\Eloquent;

trait EloquentFirstScopeTrait
{
    /**
     * First eloquent scope.
     *
     * @param $model
     * @return mixed
     */
    public static function firstScope($model): mixed
    {
        return $model->first();
    }
}
