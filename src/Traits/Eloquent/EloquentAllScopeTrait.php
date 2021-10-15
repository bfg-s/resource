<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentAllScopeTrait
{
    /**
     * Eloquent get scope.
     *
     * @param Builder|Model $model
     * @param  mixed  ...$columns
     * @return mixed
     */
    public static function allScope(Builder|Model $model, ...$columns): mixed
    {
        return $model->get($columns ?: ['*']);
    }

    /**
     * The `get` request default.
     *
     * @param  Builder|Model  $result
     * @return \Illuminate\Support\Collection
     */
    public static function getMethod(Builder|Model $result): \Illuminate\Support\Collection
    {
        return $result->get();
    }
}
