<?php
/**
 * Referrals Manager
 */
namespace Minds\Core\Referrals;

use Minds\Core\Referrals\Referral;
use Minds\Core\Referrals\Repository;
use Minds\Core\Referrals\Delegates\NotificationDelegate;
use Minds\Core\EntitiesBuilder;
use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;

class Manager
{
    /** @var Repository $repository */
    private $repository;

    /** @var Delegates\NotificationDelegate $notificationDelegate */
    private $notificationDelegate;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct(
        $repository = null,
        $notificationDelegate = null,
        $entitiesBuilder = null
    ) {
        $this->repository = $repository ?: new Repository;
        $this->notificationDelegate = $notificationDelegate ?: new Delegates\NotificationDelegate;
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * Return a list of referrals
     * @param array $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => '',
            'referrer_guid' => null,
            'hydrate' => true,
        ], $opts);

        $response = $this->repository->getList($opts);

        if ($opts['hydrate']) {
            foreach ($response as $referral) {
                $prospect = $this->entitiesBuilder->single($referral->getProspectGuid());
                $referral->setProspect($prospect);
            }
        }
        return $response;
    }

    /**
     * Create referral for pending prospect who registered for Minds
     * @param Referral $referral
     * @return bool
     */
    public function add($referral)
    {
        $this->repository->add($referral);

        // Send a notification to the referrer
        $this->notificationDelegate->notifyReferrer($referral);

        return true;
    }

    /**
     * Update referral for completed prospect who has joined rewards program
     * @param Referral $referral
     * @return bool
     */
    public function update($referral)
    {
        // Update join_timestamp
        $this->repository->update($referral);

        // Send a notification to the referrer
        $this->notificationDelegate->notifyReferrer($referral);

        return true;
    }

    /**
     * Send a notification to pending prospect to suggest they join rewards program
     * @param Referral $referral
     * @return bool
     */
    public function ping($referral)
    {
        // Ensure ping is triggered by referrer
        $urn = $referral->getUrn();
        $response = $this->repository->get($urn);
        // No response if repo finds no matches for incoming referral/prospect combo
        if (!$response) {
            return false;
        }

        // Don't ping if prospect isn't pingable
        $pingable = $response->getPingable();
        if (!$pingable) {
            return false;
        }

        // Update ping_timestamp
        $referral->setPingTimestamp(time());
        $this->repository->ping($referral);

        // Send a ping notification to the prospect
        $this->notificationDelegate->notifyProspect($referral);

        return true;
    }
}
