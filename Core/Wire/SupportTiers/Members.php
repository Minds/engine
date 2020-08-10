<?php
namespace Minds\Core\Wire\SupportTiers;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Subscriptions;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use Minds\Common\Repository\Response;

class Members
{
    /** @var Subscriptions\Manager */
    protected $subscriptionsManager;

    /** @var Manager */
    protected $supportTiersManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var SupportTier */
    protected $supportTier;

    /** @var string */
    protected $entityGuid;

    /**
     * @param Subscriptions\Manager $subscriptionsManager
     * @param Manager $supportTiersManager
     */
    public function __construct(
        $subscriptionsManager = null,
        $supportTiersManager = null,
        $entitiesBuilder = null
    ) {
        $this->subscriptionsManager = $subscriptionsManager ?? Di::_()->get('Payments\Subscriptions\Manager');
        $this->supportTiersManager = $supportTiersManager ?? new Manager();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Sets the support tier
     * @param SupportTier $supportTier
     * @return Members
     */
    public function setSupportTier(SupportTier $supportTier): Members
    {
        $members = clone $this;
        $members->supportTier = $supportTier;
        return $members;
    }

    /**
     * Sets the entityGuid
     * @param string $entityGuid
     * @return Members
     */
    public function setEntityGuid(string $entityGuid): Members
    {
        $members = clone $this;
        $members->entityGuid = $entityGuid;
        return $members;
    }

    /**
     * Returns a list of members
     * @param array $opts
     * @return Response
     */
    public function getList($opts = []): Response
    {
        if (!$this->entityGuid) {
            throw new \Exception("entityGuid must be set");
        }

        /** @var Entity */
        $entity = $this->entitiesBuilder->single($this->entityGuid);

        /** @var Subscriptions\Subscription[] */
        $subscriptions = $this->subscriptionsManager->getList([
            'plan' => 'wire',
            'entity_guid' => $entity->getGuid(),
        ]);

        /** @var SupportTier[] */
        $supportTiers = $this->supportTiersManager
            ->setEntity($entity)
            ->getAll()
            ->toArray();

        $response = new Response();

        foreach ($subscriptions as $subscription) {
            /** @var SupportTier */
            $supportTier = $this->getSupportTierBySubscription($supportTiers, $subscription);
            if (!$supportTier) {
                continue;
            }

            if ($this->supportTier && $this->supportTier->getUrn() !== $supportTier->getUrn()) {
                continue;
            }

            /** @var User */
            $user = $this->entitiesBuilder->single($subscription->getUser()->getGuid());
            if (!$user) {
                continue;
            }

            $member = new SupportTierMember();
            $member->setSupportTier($supportTier)
                ->setSubscription($subscription)
                ->setUser($user);
            
            $response[] = $member;
        }

        return $response;
    }

    /**
     * Resolves a support tiers from a list a subscription
     * @param SupportTiers[] $supportTiers
     * @param Subscriptions\Subscription $subscription
     * @return SupportTier
     */
    protected function getSupportTierBySubscription($supportTiers, Subscriptions\Subscription $subscription): ?SupportTier
    {
        $filtered = array_values(array_filter($supportTiers, function ($supportTier) use ($subscription) {
            if ($subscription->getPaymentMethod() === 'tokens' && $supportTier->hasTokens()) {
                $supportTierWei = (string) BigNumber::toPlain($supportTier->getTokens(), 18);
                return $supportTierWei == $subscription->getAmount();
            }
            if ($subscription->getPaymentMethod() === 'usd') {
                $cents = (string) $supportTier->getUsd() * 100;
                return $cents == $subscription->getAmount();
            }
            return false;
        }));

        return $filtered[0] ?? null;
    }
}
