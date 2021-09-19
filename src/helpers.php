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
