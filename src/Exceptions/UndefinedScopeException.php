<?php

namespace Bfg\Resource\Exceptions;

class UndefinedScopeException extends ResourceException
{
    public function __construct(string $scope = null)
    {
        parent::__construct(
            'Undefined scope'.($scope ? " [{$scope}]" : ''),
            409
        );
    }
}
