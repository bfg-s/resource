<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentRandomScopeTrait
{
    /**
     * Eloquent random order scope.
     *
     * @param Builder|Model $model
     * @param  string  $seed
     * @return mixed
     */
    public static function randomScope(Builder|Model $model, string $seed = ''): mixed
    {
        return $model->inRandomOrder($seed);
    }
}
