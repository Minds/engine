<?php
/**
 * Clock.
 *
 * @author edgebal
 */
namespace Minds\Core;

class Clock
{
    /**
     * Returns the current system time
     * @return int
     */
    public function now()
    {
        return time();
    }
}
