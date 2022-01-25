<?php

namespace Bfg\Resource\Traits\Model;

use Carbon\Carbon;

trait ModelTimestampsTrait
{
    /**
     * @param  Carbon|null  $carbon
     * @return string
     */
    public function getCreatedAtField(?Carbon $carbon)
    {
        return $carbon?->toDateTimeString();
    }

    /**
     * @param  Carbon|null  $carbon
     * @return string
     */
    public function getUpdatedAtField(?Carbon $carbon)
    {
        return $carbon?->toDateTimeString();
    }

    /**
     * @param  Carbon|null  $carbon
     * @return string
     */
    public function getDeletedAtField(?Carbon $carbon)
    {
        return $carbon?->toDateTimeString();
    }

    /**
     * @param  Carbon|null  $carbon
     * @return string
     */
    public function getReadAtField(?Carbon $carbon)
    {
        return $carbon?->toDateTimeString();
    }

    /**
     * @param  Carbon|null  $carbon
     * @return string
     */
    public function getStartAtField(?Carbon $carbon)
    {
        return $carbon?->toDateTimeString();
    }

    /**
     * @param  Carbon|null  $carbon
     * @return string
     */
    public function getEndAtField(?Carbon $carbon)
    {
        return $carbon?->toDateTimeString();
    }
}
