<?php
namespace Minds\Core\Wire;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Payments;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;
use Minds\Helpers\MagicAttributes;

class Thresholds
{
    /** @var SupportTiers\Manager */
    protected $supportTiersManager;

    /** @var Config */
    protected $config;

    public function __construct($supportTiersManager = null, $config = null)
    {
        $this->supportTiersManager = $supportTiersManager ?? new SupportTiers\Manager();
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Check if the entity can be shown to the passed user
     * @param User $user
     * @param $entity
     * @return bool
     * @throws \Exception
     */
    public function isAllowed($user, $entity)
    {
        if (!is_object($entity) || !(MagicAttributes::getterExists($entity, 'getWireThreshold') || method_exists($entity, 'getWireThreshold'))) {
            throw new \Exception('Entity cannot be paywalled');
        }

        if ($user && ($user->guid == $entity->getOwnerEntity()->guid || $user->isAdmin())) {
            return true;
        }

        $isPaywall = false;

        if ((MagicAttributes::getterExists($entity, 'isPayWall') || method_exists($entity, 'isPayWall')) && $entity->isPayWall()) {
            $isPaywall = true;
        }

        if (!$user && $isPaywall) {
            return false;
        }

        $threshold = $entity->getWireThreshold();

        if (!$threshold && $isPaywall) {
            $threshold = [
                'type' => 'money',
                'min' => $entity->getOwnerEntity()->getMerchant()['exclusive']['amount']
            ];
        }

        //make sure legacy posts can work
        if ($isPaywall) {
            $amount = 0;
            $minThreshold = null;

            if ($threshold['support_tier']) {
                // A very inelegant way of matching a support tier to plus
                if ($threshold['support_tier']['urn'] === $this->config->get('plus')['support_tier_urn']
                    && $user->isPlus()
                ) {
                    return true;
                }

                $supportTier = $this->supportTiersManager->getByUrn($threshold['support_tier']['urn']);
                $ownerGuid = $supportTier->getEntityGuid();
                $minThreshold = $supportTier->getUsd();
            } else {
                if (MagicAttributes::getterExists($entity, 'getOwnerGuid')) {
                    $ownerGuid = $entity->getOwnerGuid();
                } else {
                    $ownerGuid = $entity->getOwnerGUID();
                }
                $minThreshold = $threshold['min'];
                if ($threshold['type'] === 'tokens') {
                    $minThreshold = BigNumber::toPlain($threshold['min'], 18);
                }
            }

            /** @var Sums $sums */
            $sums = Di::_()->get('Wire\Sums');
            $sums->setReceiver($ownerGuid)
                ->setSender($user->guid)
                ->setFrom((new \DateTime('midnight'))->modify("-30 days")->getTimestamp());

            $tokensAmount = $sums->setMethod('tokens')->getSent() ?: 0;
            $exRate = $this->config->get('token_exchange_rate') ?: 1.25; // TODO make this is a constant
            $tokensUsdAmount = BigNumber::fromPlain($tokensAmount, 18)->toDouble() * $exRate;
            $usdAmount = $sums->setMethod('usd')->getSent();

            if (isset($threshold['type'])) {
                $allowed = BigNumber::_($tokensAmount)->sub($minThreshold)->gte(0);
            } else {
                // new support tiers
                $allowed = max($tokensUsdAmount, $usdAmount) >= $minThreshold;
            }

            if ($allowed) {
                return true;
            }

            //Plus hack (legacy posts)
            if ($entity->owner_guid == '730071191229833224') {
                $plus = (new Core\Plus\Subscription())->setUser($user);

                if ($plus->isActive()) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }
}
