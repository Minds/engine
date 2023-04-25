<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Enums;

/**
 * The user's stated goal for the boost
 */
class BoostGoal
{
    public const VIEWS = 1;
    public const ENGAGEMENT = 2;
    public const SUBSCRIBERS = 3;
    public const CLICKS = 4;

    /**
     * @var array A list of valid values for the enum - To be used for validation purposes
     */
    public const VALID = [
        self::VIEWS,
        self::ENGAGEMENT,
        self::SUBSCRIBERS,
        self::CLICKS,
    ];

    /**
     * @var array A list of goals that require goal_button_text - To be used for validation purposes
     */
    public const GOALS_REQUIRING_GOAL_BUTTON_TEXT = [
        self::SUBSCRIBERS,
        self::CLICKS
    ];

    /**
     * @var array A list of goals that require goal_button_url - To be used for validation purposes
     */
    public const GOALS_REQUIRING_GOAL_BUTTON_URL = [
        self::CLICKS
    ];
}
