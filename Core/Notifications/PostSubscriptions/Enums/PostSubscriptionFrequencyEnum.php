<?php
namespace Minds\Core\Notifications\PostSubscriptions\Enums;

enum PostSubscriptionFrequencyEnum
{
    /**
     * Notifications are sent every time an entity has an update (subject to rate limits)
     */
    case ALWAYS;

    /**
     * Notifications are sent at most once per day
     */
    case HIGHLIGHTS;

    /**
     * The user will never receive psot notifications from the entity
     */
    case NEVER;
}
