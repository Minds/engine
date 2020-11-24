<?php
/**
 * Handles pro subscriptions
 * @author mark
 */
namespace Minds\Core\Pro\Delegates;

use Minds\Entities\User;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Subscriptions;

class SubscriptionDelegate
{
    /** @var Subscriptions\Manager */
    private $subscriptionsManager;

    /** @var Config */
    private $config;

    public function __construct($subscriptionsManager = null, $config = null)
    {
        $this->subscriptionsManager = $subscriptionsManager ?? Di::_()->get('Payments\Subscriptions\Manager');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Called when pro is disabled
     * @param User $user
     * @return void
     */
    public function onDisable(User $user): void
    {
        $proUserGuid = (string) $this->config->get('pro')['handler'];

        $this->subscriptionsManager
            ->cancelSubscriptions($user->getGuid(), $proUserGuid);
    }

    /**
     * @return bool
     */
    public function hasSubscriptions(User $user): bool
    {
        $subscriptions = $this->subscriptionsManager->getList([
            'user_guid' => $user->guid,
            'entity_guid' => $this->config->get('pro')['handler']
        ]);

        return count($subscriptions) > 0;
    }
}
