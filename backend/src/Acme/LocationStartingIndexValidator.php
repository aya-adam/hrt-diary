<?php

namespace App\Acme;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LocationStartingIndexValidator
{
    public static function validate(mixed $value, ExecutionContextInterface $context, mixed $payload): void
    {
        if (null === $value)
            return;

        $object = $context->getObject();
        $locations = $object?->locations ?? null;

        if (null === $locations) {
            $context
                ->buildViolation('Value cannot be validated. Validator needs the \'locations\' property.')
                ->addViolation();

            return;
        }

        if (!is_array($locations)) {
            $context
                ->buildViolation('Value cannot be validated. The \'locations\' property needs to be an array.')
                ->addViolation();

            return;
        }

        if ($value > (count($locations) - 1)) {
            $context
                ->buildViolation(sprintf('This value cannot be greater, than the array length of \'locations\'.', $context->getPropertyPath()))
                ->addViolation();
        }
    }
}
