<?php
namespace Minds\Core\Wire\Paywall\Delegates;

use Minds\Entities\User;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Core\Analytics\Metrics\Event;

class MetricsDelegate
{

    /**
     * On unlock, record in the metrics system
     */
    public function onUnlock(PaywallEntityInterface $entity, User $user): void
    {
        $event = new Event();
        $event->setType('action')
            ->setAction('unlock')
            ->setProduct('wire')
            ->setUserGuid((string) $user->getGuid())
            ->setUserPhoneNumberHash($user->getPhoneNumberHash())
            ->setEntityGuid((string) $entity->getGuid())
            ->setEntityContainerGuid((string) $entity->getContainerGuid())
            ->setEntityType($entity->getType())
            ->setEntitySubtype((string) $entity->getSubtype())
            ->setEntityOwnerGuid((string) $entity->getOwnerGuid());

        $wireThreshold = $entity->getWireThreshold();
        if ($wireThreshold['support_tier'] ?? null) {
            $event->setSupportTierUrn($wireThreshold['support_tier']['urn']);
        }

        $event->push();
    }

}