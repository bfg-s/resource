<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentWhereScopeTrait
{
    /**
     * Eloquent where scope.
     *
     * @param  Builder|Model  $model
     * @param  string  $column
     * @param  string  $condition
     * @param  string|null  $value
     * @return mixed
     */
    public static function whereGetScope(
        Builder|Model $model,
        string $column,
        string $condition,
        string $value = null
    ): mixed {
        return $model->where(
            $column, $condition, $value
        );
    }
}
