<?php
/**
 * Referrals Manager
 */
namespace Minds\Core\Referrals;

use Minds\Core\Referrals\Repository;
use Minds\Core\Di\Di;

class Manager
{

    /** @var Repository $repository */
    private $repository;

    public function __construct(
        $repository = null,
        $entitiesBuilder = null
    )
    {
        $this->repository = $repository ?: new Repository;
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
     * Create referral for registered prospect
     * @param ReferrerGuid $referrerGuid
     * @return bool
     */
    public function add($referral)
    {
        $this->repository->add($referral);
        
        return true;
    }

    /**
     * Update referral for prospect who has joined rewards program
     * @param ProspectGuid $prospectGuid
     * @return bool
     */
    public function update($referral)
    {
        $this->repository->update($referral);   
        
        return true;
    }
}
