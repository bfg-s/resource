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
    public static function firstScope(Builder|Model $model, ...$columns): mixed
    {
        return $model->first($columns ?: ['*']);
    }

    /**
     * The `options` request default.
     *
     * @param  Builder|Model  $result
     * @return Model|Builder|null
     */
    public static function optionsMethod(Builder|Model $result): Model|Builder|null
    {
        return $result->first();
    }
}
