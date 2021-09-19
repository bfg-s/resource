<?php

namespace Bfg\Resource\Traits\Eloquent;

trait EloquentWhereScopeTrait
{
    /**
     * Eloquent where scope.
     *
     * @param $model
     * @param  array  $data
     * @param  string  $column
     * @param  string  $condition
     * @param  string|null  $value
     * @return mixed
     */
    public static function whereScope(
        $model,
        array $data,
        string $column,
        string $condition,
        string $value = null
    ): mixed {
        return $model->where(
            $column, $condition, $value
        );
    }
}
