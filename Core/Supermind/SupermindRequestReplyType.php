<?php

namespace Minds\Core\Supermind;

/**
 *
 */
class SupermindRequestReplyType
{
    public const TEXT = 0;
    public const IMAGE = 1;
    public const VIDEO = 2;
    public const LIVE = 3;

    public const VALID_REPLY_TYPES = [
        self::TEXT,
        self::IMAGE,
        self::VIDEO,
        self::LIVE
    ];
}
