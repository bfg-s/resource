<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentForPageScopeTrait
{
    /**
     * Eloquent for page scope.
     *
     * @param Builder|Model $model
     * @param  int  $page
     * @param  int  $perPage
     * @return mixed
     */
    public static function forPageGetScope(Builder|Model $model, int $page, int $perPage = 15): mixed
    {
        return $model->forPage($page, $perPage);
    }
}
