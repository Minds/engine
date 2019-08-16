<?php

namespace Minds\Common;
use ReflectionClass; 

abstract class ChannelMode
{
    const OPEN = 0;
    const MODERATED = 1;
    const CLOSED = 2;

    final public static function toArray()
    {
        return (new ReflectionClass(static::class))->getConstants();
    }

    final public static function isValid($value)
    {
        return in_array($value, static::toArray());
    }
}
