<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EloquentWithScopeTrait
{
    /**
     * First eloquent scope.
     *
     * @param  Builder|Model $model
     * @param  array  $data
     * @return mixed
     */
    public static function withScope(Builder|Model $model, ...$data): mixed
    {
        /** @var Model $model */
        return $model->with(static::withMap($data));
    }

    /**
     * @param $name
     * @param $q
     * @param $datum
     * @return mixed
     */
    protected static function recursiveWithCallNext($name, $q, $datum): mixed
    {
        return $q->with([$name => function ($q) use ($datum) {
            foreach ($datum as $key => $item) {
                static::recursiveWithCallNext($key, $q, $item);
            }
            return $q;
        }]);
    }

    /**
     * @param $data
     * @return array
     */
    protected static function withMap($data): array
    {
        $newData = [];
        $withs = [];

        foreach ($data as $datum) {
            \Arr::set($newData, str_replace('-', '.', $datum), []);
        }

        foreach ($newData as $name => $newDatum) {
            if (count($newDatum)) {
                $withs[$name] = function ($q) use ($newDatum) {
                    foreach ($newDatum as $key => $item) {
                        static::recursiveWithCallNext($key, $q, $item);
                    }
                    return $q;
                };
            } else {
                $withs[$name] = fn ($q) => $q;
            }
        }

        return $withs;
    }

    /**
     * @param $q
     * @param $datum
     * @return mixed
     */
    protected static function recursiveWithCall($q, $datum): mixed
    {
        $name = $datum[0];
        unset($datum[0]);
        $datum = array_values($datum);

        return $q->with([$name => function ($q) use ($datum) {
            if ($datum) {
                return static::recursiveWithCall($q, $datum);
            }

            return $q;
        }]);
    }
}
