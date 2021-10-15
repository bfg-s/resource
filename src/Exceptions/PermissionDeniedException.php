<?php

namespace Bfg\Resource\Exceptions;

class PermissionDeniedException extends ResourceException
{
    public function __construct(string $to = null)
    {
        parent::__construct(
            'Permission denied'.($to ? ' to ['.$to.']' : ''),
            403
        );
    }
}
