<?php

if (! function_exists('route_real_param')) {
    /**
     * Generate real route param.
     *
     * @param $data
     * @return mixed
     */
    function route_real_param($data): mixed
    {
        if (is_numeric($data)) {
            $data = $data == (int) $data ? (int) $data : (float) $data;
        } else {
            if ($data === 'true') {
                $data = true;
            } else {
                if ($data === 'false') {
                    $data = false;
                } else {
                    if ($data === 'null') {
                        $data = null;
                    } else {
                        if (is_string($data)) {
                            $data = str_replace('*', '%', $data);
                        }
                    }
                }
            }
        }

        return $data;
    }
}

if (! function_exists('is_assoc')) {
    /**
     * Check whether an array is associative.
     *
     * @param  array  $arr
     * @return bool
     */
    function is_assoc(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

if (! function_exists('multi_dot_call')) {
    /**
     * Access to an object or/and an array using the dot path method.
     *
     * @param $obj
     * @param  string  $dot_path
     * @param  bool  $locale
     * @return mixed
     */
    function multi_dot_call($obj, string $dot_path, bool $locale = true): mixed
    {
        return \Bfg\Resource\Accessor::create($obj)->dotCall($dot_path, $locale);
    }
}
