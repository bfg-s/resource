<?php

namespace Bfg\Resource\Traits;

use Bfg\Resource\Traits\Eloquent\EloquentFindScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentFirstScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentGetScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentPaginateScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentWhereScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentWithScopeTrait;

trait EloquentScopesTrait
{
    use EloquentFindScopeTrait,
        EloquentGetScopeTrait,
        EloquentPaginateScopeTrait,
        EloquentWhereScopeTrait,
        EloquentFirstScopeTrait,
        EloquentWithScopeTrait;
}
