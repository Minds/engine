<?php
namespace Minds\Core\Notifications\Push\Settings;

use Minds\Common\Repository\AbstractRepositoryOpts;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 */
class SettingsListOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $userGuid;
}
