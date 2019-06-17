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
        $repository = null
    )
    {
        $this->repository = $repository ?: new Repository;
    }

    /**
     * Create referral for registered prospect
     * @param ReferrerGuid $referrerGuid
     * @return bool
     */
    public function add($referral)
    {
        // OJMQ: what happens if the add() fails?
        // i.e. how is it that there's no scenario here where outcome returns false?     
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
        // OJMQ: what happens if the update() fails?
        // i.e. how is it that there's no scenario here where outcome returns false?    
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
        
        return $response;
    }


}
