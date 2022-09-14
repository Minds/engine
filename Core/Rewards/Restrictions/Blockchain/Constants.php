<?php

namespace Minds\Core\Rewards\Restrictions\Blockchain;

/**
 * Constants used for Blockchain Restrictions.
 */
class Constants
{
    /** @var array ALLOWED_REASONS - permitted reasons */
    const ALLOWED_REASONS = ['ofac', 'custom'];

    /** @var array ALLOWED_NETWORKS - permitted networks */
    const ALLOWED_NETWORKS = ['ETH', 'XBT'];
}
