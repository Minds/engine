<?php
namespace Minds\Core\Wire\SupportTiers\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Sessions\ActiveSession;
use Minds\Core\Payments\Subscriptions;
use Minds\Core\Util\BigNumber;
use Minds\Core\Wire\SupportTiers\SupportTier;
use Minds\Entities\User;

class PaymentsDelegate
{
    /** @var Subscriptions\Manager */
    protected $subscriptionsManager;

    /** @var PsrWrapper */
    protected $cache;

    /** @var ActiveSession */
    protected $activeSession;

    public function __construct($subscriptionsManager = null, $cache = null, $activeSession = null)
    {
        $this->subscriptionsManager = $subscriptionsManager ?? Di::_()->get('Payments\Subscriptions\Manager');
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
        $this->activeSession = $activeSession ?? Di::_()->get('Sessions\ActiveSession');
    }

    /**
     * Hydrates the SupportTier with payment data
     * @param SupportTier $supportTier
     * @return SupportTier
     */
    public function hydrate(SupportTier $supportTier): SupportTier
    {
        /** @var User */
        $currentUser = $this->activeSession->getUser();
        if (!$currentUser) {
            return $supportTier;
        }

        if ($currentUser->getGuid() == $supportTier->getEntityGuid()) {
            return $supportTier; // Same user
        }

        // Grab subscriptions

        /** @var Subscriptions\Subscription[] */
        $subscriptions = $this->getSubscriptions($supportTier->getEntityGuid(), (string) $currentUser->getGuid());

        foreach ($subscriptions as $subscription) {
            $method = $subscription->getPaymentMethod();

            if ($supportTier->hasTokens() && $method === 'tokens') {
                $supportTierWei = (string) BigNumber::toPlain($supportTier->getTokens(), 18);
                if ($supportTierWei == $subscription->getAmount()) {
                    $supportTier->setSubscriptionUrn($subscription->getId());
                }
            }

            if ($method === 'usd') {
                $cents = (string) $supportTier->getUsd() * 100;
                if ($cents == $subscription->getAmount()) {
                    $supportTier->setSubscriptionUrn($subscription->getId());
                }
            }
        }

        return $supportTier;
    }

    /**
     * Returns a cached list of subscriptions between entity and user
     * @param string $entityGuid
     * @param string $userGuid
     * @return Subscriptions\Subscription[]
     */
    protected function getSubscriptions(string $entityGuid, string $userGuid): array
    {
        $cacheKey = "support-tier-subscriptions:$entityGuid-$userGuid";

        // if ($cached = $this->cache->get($cacheKey)) {
        //     return unserialize($cached);
        // }

        $subscriptions = $this->subscriptionsManager
            ->getList([
                'user_guid' => $userGuid,
                'entity_guid' => $entityGuid,
            ]);

        $this->cache->set($cacheKey, serialize($subscriptions), 300); // Cache for 5 mins

        return $subscriptions;
    }
}
