<?php

namespace Minds\Core\Rewards\Restrictions\Blockchain;

use Minds\Core\Channels\Ban;
use Minds\Core\Di\Di;
use Minds\Core\Reports\Report;
use Minds\Core\Reports\Verdict\Delegates\EmailDelegate;
use Minds\Core\Rewards\Restrictions\Blockchain\RestrictedException;
use Minds\Entities\User;

/**
 * Manager, orchestrates logic around Restrictions.
 */
class Manager
{
    public function __construct(
        private ?Repository $repository = null,
        private ?Ban $banManager = null,
        private ?EmailDelegate $emailDelegate = null
    ) {
        $this->repository ??= Di::_()->get('Rewards\Restrictions\Blockchain\Repository');
        $this->banManager ??= Di::_()->get('Channels\Ban');
        $this->emailDelegate ??= new EmailDelegate();
    }

    /**
     * Get a ALL restrictions.
     * @return array array of all Restrictions.
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Get a single restriction by address.
     * @param string $address - address to get.
     * @return array array of matching Restrictions.
     */
    public function get(string $address): array
    {
        return $this->repository->get($address);
    }

    /**
     * Add a restriction to the repository.
     * @param Restriction $restriction - object containing data on restricting to add.
     * @return boolean true if restriction was added.
     */
    public function add(Restriction $restriction): bool
    {
        return $this->repository->add($restriction);
    }

    /**
     * Delete from repository
     * @param string $address - address to delete entries for.
     * @return boolean true if address was deleted.
     */
    public function delete(string $address): bool
    {
        return $this->repository->delete($address);
    }

    /**
     * Whether address is restricted.
     * @param string $address - address to check.
     * @return boolean true if address is restricted.
     */
    public function isRestricted(string $address): bool
    {
        return count($this->get($address)) > 0;
    }

    /**
     * Check that an address is not restricted. Will ban if a user is restricted.
     * @param string $address - address to check.
     * @param string $user - user to check for.
     * @throws RestrictedException - thrown if a user is restricted.
     * @return void - returns void if gatekeeper is passed.
     */
    public function gatekeeper(string $address, User $user): void
    {
        if ($this->isRestricted($address)) {
            $this->banManager
                ->setUser($user)
                ->ban('1.4');

            $this->emailDelegate->onBan(
                (new Report())
                    ->setEntityUrn($user->getUrn())
                    ->setReasonCode(1)
                    ->setSubReasonCode(4)
            );
        
            throw new RestrictedException();
        }
    }
}
