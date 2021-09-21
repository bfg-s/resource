<?php

namespace Bfg\Resource\Traits\Eloquent;

trait EloquentAllScopeTrait
{
    /**
     * Eloquent get scope.
     *
     * @param $model
     * @return mixed
     */
    public static function allScope($model): mixed
    {
        return $model->get();
    }
}
