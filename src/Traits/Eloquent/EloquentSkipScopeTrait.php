<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentSkipScopeTrait
{
    /**
     * Eloquent skip scope.
     *
     * @param  Builder|Model  $model
     * @param  mixed  ...$ids
     * @return mixed
     */
    public static function skipScope(Builder|Model $model, ...$ids): mixed
    {
        foreach ($ids as $id) {
            $model = $model->skip($id);
        }

        return $model;
    }
}
