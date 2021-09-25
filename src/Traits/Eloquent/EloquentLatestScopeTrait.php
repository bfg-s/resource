<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentLatestScopeTrait
{
    /**
     * Eloquent latest scope.
     *
     * @param Builder|Model $model
     * @param  string  $column
     * @return mixed
     */
    public static function latestScope(Builder|Model $model, string $column = 'id'): mixed
    {
        return $model->latest($column);
    }
}
