<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Settings;

/**
 * A user may specify what suitability (if any) they
 * want for other users' boosts that are shown on their channel
 */
class BoostPartnerSuitability
{
    const DISABLED = 1;
    const SAFE = 2;
    const CONTROVERSIAL = 3;
}
