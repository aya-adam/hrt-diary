<?php

namespace App\Util;

use DateTimeImmutable;
use DateTimeInterface;

class DateImmutableTransformer
{
    public static function format(DateTimeInterface|string|null $value, object $source): DateTimeImmutable|string|null
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Unexpected "%s" source format. Y-m-d expected (e.g. 2026-12-31).', $value));
        }

        return DateTimeImmutable::createFromFormat('Y-m-d', $value)->setTime(0, 0, 0, 0);
    }
}
