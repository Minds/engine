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
     * Create referral for registered prospect
     * @param ReferrerGuid $referrerGuid
     * @return bool
     */
    public function add($referral)
    {
        // OJMTODO: return the value of repository add to solve
        // OJMQ: what does 'return the value of repository add to solve' mean?  
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
        // OJMTODO: return the value of repository update to solve   
        $this->repository->update($referral);
        
        return true;
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
            'referrer_guid' => null,
            'hydrate' => true,
        ], $opts);

        $response = $this->repository->getList($opts);

        // hydrate prospect here
        if ($opts['hydrate']) { 
            foreach ($response as $referral) {           
                $prospect = $this->entitiesBuilder->single($referral->getProspectGuid());
                $referral->setProspect($prospect);
            }
        }

        return $response;
    }


}
