<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Enums;

enum AnalyticsMetricEnum
{
    /**
     * Users who have had at least one session in the day
     */
    case DAILY_ACTIVE_USERS;

    /**
     * Newly registered users
     */
    case NEW_USERS;

    /**
     * The total number of users
     */
    case TOTAL_USERS;

    /**
     * The number of unique visitors
     */
    case VISITORS;

    /**
     * The average (mean) session duration in seconds
     */
    case MEAN_SESSION_SECS;

    /**
     * The total number of active site membership subscriptions
     */
    case TOTAL_SITE_MEMBERSHIP_SUBSCRIPTIONS;

}
