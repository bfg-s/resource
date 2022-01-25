<?php

namespace Bfg\Resource\Traits\Model;

trait ModelAssetsTrait
{
    /**
     * @param $value
     * @return string
     */
    public function getPhotoField($value)
    {
        return ! $value || str_starts_with($value, 'http') ? $value : asset($value);
    }

    /**
     * @param $value
     * @return string
     */
    public function getCoverField($value)
    {
        return ! $value || str_starts_with($value, 'http') ? $value : asset($value);
    }

    /**
     * @param $value
     * @return string
     */
    public function getFileField($value)
    {
        return ! $value || str_starts_with($value, 'http') ? $value : asset($value);
    }
}
