<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Enums;

/**
 * If the boost has a custom CTA button, what does the button say?
 * */
class BoostGoalButtonText
{
    public const SUBSCRIBE_TO_MY_CHANNEL = 1;
    public const GET_CONNECTED = 2;
    public const STAY_IN_THE_LOOP = 3;
    public const LEARN_MORE = 4;
    public const GET_STARTED = 5;
    public const SIGN_UP = 6;
    public const TRY_FOR_FREE = 7;
    public const SHOP_NOW = 8;
    public const BUY_NOW = 9;

    /**
     * @var array A list of all valid values for the enum when the boost goal is subscribers - To be used for validation purposes
     */
    public const VALID_GOAL_BUTTON_TEXTS_WHEN_GOAL_IS_SUBSCRIBERS = [
        self::SUBSCRIBE_TO_MY_CHANNEL,
        self::GET_CONNECTED,
        self::STAY_IN_THE_LOOP
    ];

    /**
     * @var array A list of all valid values for the enum when the boost goal is clicks - To be used for validation purposes
     */
    public const VALID_GOAL_BUTTON_TEXTS_WHEN_GOAL_IS_CLICKS = [
        self::LEARN_MORE,
        self::GET_STARTED,
        self::SIGN_UP,
        self::TRY_FOR_FREE,
        self::SHOP_NOW,
        self::BUY_NOW
    ];
}
