<?php
/**
 * Referrals Manager
 */
namespace Minds\Core\Referrals;

use Minds\Core\Referrals\Repository;

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
    public function getList($referral)
    {
        $response = $this->repository->getList($referral);

        // OJMQ: if I hydrate here, does it mean I'd have to change the data model 
        // OJMQ: to include the prospect entity? (did it)

        // OJMQ: if this works - how is it that I am accessing getProspectGuid()? 
        // OJMQ: i.e. how is it accessing the data model
        foreach ($response as $i => $referralRow) {           
            $referralRow->setProspect($this->entitiesBuilder->single($referralRow->getProspectGuid()));
        }

        return $response;
    }


}
