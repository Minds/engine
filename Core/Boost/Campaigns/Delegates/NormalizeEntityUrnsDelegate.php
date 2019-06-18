<?php
/**
 * NormalizeEntityUrnsDelegate
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns\Delegates;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use Minds\Helpers\Text;

class NormalizeEntityUrnsDelegate
{
    /** @var ACL */
    protected $acl;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /**
     * NormalizeEntityUrnsDelegate constructor.
     * @param ACL $acl
     * @param EntitiesBuilder $entitiesBuilder
     */
    public function __construct(
        $acl = null,
        $entitiesBuilder = null
    )
    {
        $this->acl = $acl ?: ACL::_();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     */
    public function onCreate(Campaign $campaign)
    {
        $owner = new User();
        $owner->set('guid', $campaign->getOwnerGuid());

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
            } elseif (!$this->acl->read($entity, $owner)) {
                throw new CampaignException("Entity {$entityUrn} is not readable");
            }
        }

        $campaign
            ->setEntityUrns($entityUrns);

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @param Campaign $oldCampaign
     * @return Campaign
     * @throws CampaignException
     */
    public function onUpdate(Campaign $campaign, Campaign $oldCampaign)
    {
        $campaign = $this->onCreate($campaign);

        if ($campaign->getEntityUrns() !== $oldCampaign->getEntityUrns()) {
            throw new CampaignException("Campaigns cannot change their content after created");
        }

        return $campaign;
    }
}
