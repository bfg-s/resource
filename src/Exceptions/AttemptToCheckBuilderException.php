<?php

namespace Bfg\Resource\Exceptions;

class AttemptToCheckBuilderException extends ResourceException
{
    public function __construct()
    {
        parent::__construct(
            'You can not use the attribute with scope which processes builders.',
            400
        );
    }
}
