<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentFirstScopeTrait
{
    /**
     * First eloquent scope.
     *
     * @param  Builder|Model  $model
     * @param  mixed  ...$columns
     * @return mixed
     */
    public static function firstGetScope(Builder|Model $model, ...$columns): mixed
    {
        return $model->first($columns ?: ['*']);
    }
}
