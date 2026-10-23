<?php

namespace App\State\Trait;

use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Operation;

trait GraphQlOperationHelperTrait
{
    private static function isMutationCreate(Operation $operation): bool
    {
        return $operation instanceof Mutation && $operation->getName() === 'create';
    }

    private static function isMutationUpdate(Operation $operation): bool
    {
        return $operation instanceof Mutation && $operation->getName() === 'update';
    }
    
    private static function isMutationAuthLogin(Operation $operation): bool
    {
        return $operation instanceof Mutation && $operation->getShortName() === 'Auth' && $operation->getName() === 'get';
    }
    
    private static function isMutationAuthRefresh(Operation $operation): bool
    {
        return $operation instanceof Mutation && $operation->getShortName() === 'Auth' && $operation->getName() === 'refresh';
    }
    
    private static function isMutationAuthRevoke(Operation $operation): bool
    {
        return $operation instanceof Mutation && $operation->getShortName() === 'Auth' && $operation->getName() === 'revoke';
    }
}
