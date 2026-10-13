<?php

namespace App\Util;

use DateInterval;

class DateIntervalTransformer
{
    public static function format(DateInterval|string|null $value, object $source): DateInterval|string|null
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof DateInterval) {
            return $value->format('P%yY%mM%dDT%hH%iM%sS');
        }

        if (!preg_match('/P(?!$)(?:\d+Y)?(?:\d+M)?(?:\d+W)?(?:\d+D)?(?:T(?=[\d+])(?:\d+H)?(?:\d+M)?(?:\d+S)?)?/', $value)) {
            throw new \InvalidArgumentException(sprintf('Unexpected "%s" DateInterval format. ISO-8601 expected (e.g. P0Y0M0DT0H0M0S).', $value));
        }

        return new DateInterval($value);
    }
}
