<?php
declare(strict_types=1);

namespace Minds\Core\Settings\GraphQL\Enums;

/**
 * Valid keys for Dismissal objects.
 */
enum DismissalKeyEnum: string
{
    case DISCOVERY_PLUS_EXPLAINER = 'DISCOVERY_PLUS_EXPLAINER';
    case BOOST_CONSOLE_EXPLAINER = 'BOOST_CONSOLE_EXPLAINER';
    case WALLET_EXPLAINER = 'WALLET_EXPLAINER';
    case ANALYTICS_EXPLAINER = 'ANALYTICS_EXPLAINER';
    case GROUPS_EXPLAINER = 'GROUPS_EXPLAINER';
}
