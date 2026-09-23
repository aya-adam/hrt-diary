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
}
