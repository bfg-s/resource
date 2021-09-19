<?php

namespace Bfg\Resource\Traits\Eloquent;

trait EloquentFindScopeTrait
{
    /**
     * Find by id eloquent scope.
     *
     * @param $model
     * @param  array  $data
     * @param  int  $id
     * @return mixed
     */
    public static function findScope($model, array $data, int $id): mixed
    {
        return $model->find($id);
    }
}
