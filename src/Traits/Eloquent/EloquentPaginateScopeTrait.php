<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentPaginateScopeTrait
{
    /**
     * Eloquent paginate get scope.
     *
     * @param  Builder|Model  $model
     * @param  int|null  $perPage
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  mixed  ...$columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function paginateScope(
        Builder|Model $model,
        int $perPage = null,
        string $pageName = 'page',
        int $page = null,
        ...$columns
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        /** @var Model $model */
        return $model->paginate($perPage, $columns ?: ['*'], $pageName, $page);
    }
}
