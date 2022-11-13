<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentFindScopeTrait
{
    /**
     * Find by id eloquent scope.
     *
     * @param  Builder|Model $model
     * @param  array  $data
     * @param  int  $id
     * @return mixed
     */
    public static function findGetScope(Builder|Model $model, int $id, ...$data): mixed
    {
        return $model->find($id, $data ?: ['*']);
    }
}
