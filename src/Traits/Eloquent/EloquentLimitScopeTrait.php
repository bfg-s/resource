<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentLimitScopeTrait
{
    /**
     * Eloquent limit scope.
     *
     * @param Builder|Model $model
     * @param  int  $count
     * @return mixed
     */
    public static function limitScope(Builder|Model $model, int $count): mixed
    {
        return $model->limit($count);
    }
}
