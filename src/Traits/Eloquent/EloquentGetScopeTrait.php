<?php

namespace Bfg\Resource\Traits\Eloquent;

trait EloquentGetScopeTrait
{
    /**
     * Eloquent get scope.
     *
     * @param $model
     * @return mixed
     */
    public static function getScope($model): mixed
    {
        return $model->get();
    }
}
