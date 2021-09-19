<?php

namespace Bfg\Resource\Traits\Eloquent;

use Illuminate\Database\Eloquent\Model;

trait EloquentWithScopeTrait
{
    /**
     * First eloquent scope.
     *
     * @param $model
     * @param  array  $data
     * @return mixed
     */
    public static function withScope($model, array $data): mixed
    {
        //dump(static::withMap($data));
        /** @var Model $model */
        return $model->with(static::withMap($data));
    }

    protected static function recursiveWithCallNext($name, $q, $datum)
    {
        return $q->with([$name => function ($q) use ($datum) {
            foreach ($datum as $key => $item) {
                static::recursiveWithCallNext($key, $q, $item);
            }

            return $q;
        }]);
    }

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
                        //dump($key, $item);
                        static::recursiveWithCallNext($key, $q, $item);
                    }

                    return $q;
                };
            } else {
                $withs[$name] = fn ($q) => $q;
            }
        }

        //dd($withs);

//        foreach ($data as $datum) {
//            $datum = explode('-', $datum);
//            $name = $datum[0];
//            unset($datum[0]);
//            $datum = array_values($datum);
//            if (count($datum)) {
//                $withs[$name] = function ($q) use ($datum) {
//                    return static::recursiveWithCall($q, $datum);
//                };
//            } else {
//                $withs[$name] = fn($q) => $q;
//            }
//        }

        return $withs;
    }

    /**
     * @param $q
     * @param $datum
     * @return mixed
     */
    protected static function recursiveWithCall($q, $datum)
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
