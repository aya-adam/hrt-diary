<?php

namespace App\Util;

use DateTimeImmutable;
use DateTimeInterface;

class DateTimeImmutableTransformer
{
    public static function format(DateTimeInterface|string|null $value, object $source): DateTimeImmutable|string|null
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if (!preg_match('/\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d/', $value)) {
            throw new \InvalidArgumentException(sprintf('Unexpected "%s" source format. %s expected (e.g. %s).', $value, DateTimeInterface::ATOM, new DateTimeImmutable()->format(DateTimeInterface::ATOM)));
        }

        return DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $value);
    }
}
