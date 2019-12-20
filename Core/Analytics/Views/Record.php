<?php

namespace Minds\Core\Analytics\Views;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Helpers\Counters;

/**
 * Class Record
 * @package Minds\Core\Analytics\Views
 */
class Record
{
    /** @var Manager */
    protected $manager;
    /** @var Core\Referrals\ReferralCookie $referralCookie */
    protected $referralCookie;
    /** @var string $lastError */
    protected $lastError = '';
    /** @var array $boostData */
    protected $boostData;
    /** @var string $identifier **/
    protected $identifier = '';
    /** @var array $clientMeta */
    protected $clientMeta;

    public function __construct(Manager $manager=null, Core\Referrals\ReferralCookie $referralCookie=null)
    {
        $this->manager = $manager ?: new Manager();
        $this->referralCookie = $referralCookie ?: Di::_()->get('Referrals\Cookie');
    }

    /**
     * Set the client meta
     * @param array $clientMeta
     * @return $this
     */
    public function setClientMeta(array $clientMeta): self
    {
        $this->clientMeta = $clientMeta;
        return $this;
    }

    /**
     * Set the identifier
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Get boost impressions data
     * @return array
     */
    public function getBoostImpressionsData(): array
    {
        return $this->boostData;
    }

    /**
     * @return string
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Record a boost
     * @return bool
     */
    public function recordBoost(): bool
    {
        /** @var Core\Boost\Network\Metrics $metrics */
        $metrics = Di::_()->get('Boost\Network\Metrics');
        /** @var Core\Boost\Network\Manager $manager */
        $manager = Di::_()->get('Boost\Network\Manager');

        $urn = "urn:boost:newsfeed:{$this->identifier}";

        $boost = $manager->get($urn, [ 'hydrate' => true ]);
        if (!$boost) {
            $this->lastError = 'Could not find boost';
            return false;
        }

        $impressionsTotal = $metrics->incrementTotalViews($boost);
        $impressionsRequested = $boost->getImpressions();
        $impressionsDaily = $metrics->incrementDailyViews($boost);

        $this->boostData = [
            'impressions' => $impressionsRequested,
            'impressions_met' => $impressionsTotal,
            'impressions_daily' => $impressionsDaily
        ];

        if ($boost->getBoostType() === Core\Boost\Network\Boost::BOOST_TYPE_CAMPAIGN) {
            $impressionsDailyCap = $boost->getDailyCap();
            if ($impressionsDaily >= $impressionsDailyCap) {
                // TODO: Pause campaign with status notification when daily cap reached
                error_log("boost|pause|daily:{$impressionsDaily}|cap:{$impressionsDailyCap}");
            }
        } else {
            if ($impressionsTotal >= $impressionsRequested) {
                $manager->expire($boost);
            }
        }

        $this->boostData = [
            'impressions' => $impressionsRequested,
            'impressions_met' => $impressionsTotal,
            'impressions_daily' => $impressionsDaily
        ];

        Counters::increment($boost->getEntity()->guid, "impression");
        Counters::increment($boost->getEntity()->owner_guid, "impression");

        try {
            $this->manager->record(
                (new View())
                    ->setEntityUrn($boost->getEntity()->getUrn())
                    ->setOwnerGuid((string) $boost->getEntity()->getOwnerGuid())
                    ->setClientMeta($this->clientMeta)
            );
        } catch (\Exception $e) {
            error_log($e);
        }

        return true;
    }

    /**
     * Record an entity
     * @return bool
     */
    public function recordEntity(): bool
    {
        $entity = Entities\Factory::build($this->identifier);

        if (!$entity) {
            $this->lastError = 'Could not the entity';
            return false;
        }

        if ($entity->type === 'activity') {
            try {
                Core\Analytics\App::_()
                    ->setMetric('impression')
                    ->setKey($entity->guid)
                    ->increment();

                if ($entity->remind_object) {
                    Core\Analytics\App::_()
                        ->setMetric('impression')
                        ->setKey($entity->remind_object['guid'])
                        ->increment();

                    Core\Analytics\App::_()
                        ->setMetric('impression')
                        ->setKey($entity->remind_object['owner_guid'])
                        ->increment();
                }

                Core\Analytics\User::_()
                    ->setMetric('impression')
                    ->setKey($entity->owner_guid)
                    ->increment();
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
        }

        try {
            $this->manager->record(
                (new Core\Analytics\Views\View())
                    ->setEntityUrn($entity->getUrn())
                    ->setOwnerGuid((string) $entity->getOwnerGuid())
                    ->setClientMeta($this->clientMeta)
            );
        } catch (\Exception $e) {
            error_log($e);
        }

        $this->referralCookie->setEntity($entity)->create();
        return true;
    }
}
