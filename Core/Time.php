<?php

namespace Minds\Core;

class Time
{
    const HALF_HOUR = 1800;
    const ONE_HOUR = 3600;
    const TWO_HOUR = 7200;
    const ONE_DAY = 86400;
    const ONE_MIN = 60;
    const FIVE_MIN = 300;
    const TEN_MIN = 600;
    const FIFTEEN_MIN = 900;

    /**
     * Return the interval timestamp a timestamp is within
     * @param int $ts
     * @param int $interval
     * @return int
     */
    public static function toInterval(int $ts, int $interval)
    {
        return $ts - ($ts % $interval);
    }

    /**
     * Return an array of interval values between two timestamps
     * @param int $start
     * @param int $end
     * @param int $interval
     * @return array
     */
    public static function intervalsBetween(int $start, int $end, int $interval): array
    {
        $startTs = self::toInterval($start, $interval);
        $endTs = self::toInterval($end, $interval);

        /* Exclusive not inclusive range should ignore first interval value */

        if ($startTs < $endTs) {
            $startTs += $interval;
        }

        $intervals = [];

        while ($startTs <= $endTs) {
            $intervals[] = $startTs;
            $startTs += $interval;
        }

        return $intervals;
    }
}
