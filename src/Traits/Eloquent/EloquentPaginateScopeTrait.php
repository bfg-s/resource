<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Model;

trait EloquentPaginateScopeTrait
{
    /**
     * Eloquent paginate get scope.
     *
     * @param $model
     * @param  array  $data
     * @param  int|null  $perPage
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function paginateScope(
        $model,
        array $data,
        int $perPage = null,
        string $pageName = 'page',
        int $page = null
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        /** @var Model $model */
        return $model->paginate($perPage, ['*'], $pageName, $page);
    }
}
