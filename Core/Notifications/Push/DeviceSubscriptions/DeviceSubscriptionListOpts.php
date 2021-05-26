<?php
namespace Minds\Core\Notifications\Push\DeviceSubscriptions;

use Minds\Common\Repository\AbstractRepositoryOpts;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setLimit(int $limit)
 * @method int getLimit()
 */
class DeviceSubscriptionListOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $userGuid;

    /** @var int */
    protected $limit = 24;
}
