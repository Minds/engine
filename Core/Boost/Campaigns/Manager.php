<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\GuidBuilder;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use Minds\Helpers\Text;

class Manager
{
    /** @var Repository  */
    protected $repository;

    /** @var GuidBuilder */
    protected $guid;

    /** @var ACL */
    protected $acl;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var User */
    protected $actor;

    /**
     * Manager constructor.
     * @param Repository $repository
     * @param GuidBuilder $guid
     * @param ACL $acl
     * @param EntitiesBuilder $entitiesBuilder
     */
    public function __construct(
        $repository = null,
        $guid = null,
        $acl = null,
        $entitiesBuilder = null
    )
    {
        $this->repository = $repository ?: new Repository();
        $this->guid = $guid ?: Di::_()->get('Guid');
        $this->acl = $acl ?: ACL::_();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param User $actor
     * @return Manager
     */
    public function setActor(User $actor)
    {
        $this->actor = $actor;
        return $this;
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     */
    public function create(Campaign $campaign)
    {
        // Validate that there's no URN

        if ($campaign->getUrn()) {
            throw new CampaignException('Campaign already has an URN');
        }

        // Validate that there's a name

        if (!$campaign->getName()) {
            throw new CampaignException('Campaign should have a name');
        }

        // Validate type

        $validTypes = ['newsfeed', 'content', 'banner', 'video'];

        if (!in_array($campaign->getType(), $validTypes)) {
            throw new CampaignException('Invalid campaign type');
        }

        // Normalize and validate dates

        $start = intval($campaign->getStart() / 1000);
        $end = intval($campaign->getEnd() / 1000);

        if ($start <= 0 || $end <= 0) {
            throw new CampaignException('Campaign should have a start and end date');
        }

        $today = strtotime(date('Y-m-d') . ' 00:00:00') * 1000;
        $start = strtotime(date('Y-m-d', $start) . ' 00:00:00') * 1000;
        $end = strtotime(date('Y-m-d', $end) . ' 23:59:59') * 1000;

        if ($start < $today) {
            throw new CampaignException('Campaign start should not be in the past');
        } elseif ($start >= $end) {
            throw new CampaignException('Campaign end before starting');
        }

        $campaign
            ->setStart($start)
            ->setEnd($end);

        // Validate budget

        if (!$campaign->getBudget() || $campaign->getBudget() <= 0) {
            throw new CampaignException('Campaign should have a budget');
        }

        // TODO: Validate offchain balance, or set as pending for onchain

        // Generate URN

        $guid = $this->guid->build();
        $urn = "urn:campaign:{$guid}";

        $campaign
            ->setUrn($urn);

        // Normalize and validate entity URNs

        $entityUrns = array_values(array_unique(array_filter(Text::buildArray($campaign->getEntityUrns()))));

        if (!$entityUrns) {
            throw new CampaignException('Campaign should have at least an entity');
        }

        $entityUrns = array_map(function ($entityUrn) {
            if (is_numeric($entityUrn)) {
                $entityUrn = "urn:entity:{$entityUrn}";
            }

            return $entityUrn;
        }, $entityUrns);

        foreach ($entityUrns as $entityUrn) {
            // TODO: Should we use entity resolver?
            try {
                $entityUrn = new Urn($entityUrn);
            } catch (Exception $e) {
                throw new CampaignException("URN {$entityUrn} is not valid: {$e->getMessage()}");
            }

            $guid = $entityUrn->getNss();
            $entity = $this->entitiesBuilder->single($guid);

            if (!$entity) {
                throw new CampaignException("Entity {$entityUrn} doesn't exist");
            }

            if ($this->actor && !$this->acl->read($entity, $this->actor)) {
                throw new CampaignException("Entity {$entityUrn} is not readable");
            }
        }

        $campaign->setEntityUrns($entityUrns);

        // Normalize hashtags

        $hashtags = $campaign->getHashtags();

        if (is_string($hashtags)) {
            $hashtags = explode(' ', $hashtags);
        }

        $campaign->setHashtags(array_values(array_unique(array_filter(array_map(function ($hashtag) {
            return preg_replace('/[^a-zA-Z_]/', '', $hashtag);
        }, $hashtags)))));

        //

        $done = $this->repository->add($campaign);

        // TODO: Assign ->setBoost()

        if (!$done) {
            throw new CampaignException('Cannot save campaign');
        }

        return $campaign;
    }
}
