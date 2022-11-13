<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * @return LengthAwarePaginator
     */
    public static function paginateGetScope(
        Builder|Model $model,
        int $perPage = null,
        string $pageName = 'page',
        int $page = null,
        ...$columns
    ): LengthAwarePaginator {
        /** @var Model $model */
        return $model->paginate($perPage, $columns ?: ['*'], $pageName, $page);
    }
}
