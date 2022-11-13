<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentOrderByScopeTrait
{
    /**
     * Eloquent order by scope.
     *
     * @param  Builder|Model  $model
     * @param  string  $column
     * @param  string  $direction
     * @return mixed
     */
    public static function orderByGetScope(Builder|Model $model, string $column, string $direction = 'asc'): mixed
    {
        return $model->orderBy($column, $direction);
    }

    /**
     * Eloquent order by desc scope.
     *
     * @param  Builder|Model  $model
     * @param  string  $column
     * @return mixed
     */
    public static function orderByDescGetScope(Builder|Model $model, string $column): mixed
    {
        return $model->orderByDesc($column);
    }
}
