<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Interfaces;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Rewards\Restrictions\Blockchain\Manager;
use Minds\Core\Rewards\Restrictions\Blockchain\Restriction;
use Minds\Core\Rewards\Restrictions\Blockchain\RestrictedException;

/**
 * CLI for checking and changing the state of blockchain restrictions.
 */
class BlockchainRestrictions extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(
        private ?Manager $manager = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->manager ??= Di::_()->get('Rewards\Restrictions\Blockchain\Manager');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $this->out('See help');
    }

    /**
     * Get all restrictions and output information.
     * @example
     * - php cli.php BlockchainRestrictions getAll
     * @return void
     */
    public function getAll(): void
    {
        $restrictions = $this->manager->getAll();

        foreach ($restrictions as $restriction) {
            $this->out($restriction);
        }
    }

    /**
     * Get single restriction by address and output information.
     * @example
     * - php cli.php BlockchainRestrictions get --address='0x000000000000000000000000000000000000dead'
     * @return void
     */
    public function get(): void
    {
        $address = $this->getOpt('address') ?? false;
          
        if (!$address) {
            $this->out('address flags must be provided');
            return;
        }

        $restrictions = $this->manager->get($address);

        foreach ($restrictions as $restriction) {
            $this->out($restriction);
        }
    }

    /**
     * Check is an address is restricted WITHOUT acting upon it.
     * @example
     * - php cli.php BlockchainRestrictions isRestricted --address='0x000000000000000000000000000000000000dead'
     * @return void
     */
    public function isRestricted(): void
    {
        $address = $this->getOpt('address') ?? false;
          
        if (!$address) {
            $this->out('address flags must be provided');
            return;
        }

        $restricted = $this->manager->isRestricted($address);
        
        $state = $restricted ? 'restricted' : 'not restricted';
        $this->out("Address: $address is {$state}");
    }

    /**
     * Add a restriction.
     * @example
     * - php cli.php BlockchainRestrictions add --address='0x000000000000000000000000000000000000dead' --network='ethereum' --reason='ofac';
     * @return void
     */
    public function add()
    {
        $address = $this->getOpt('address') ?? false;
        $reason = $this->getOpt('reason') ?? false;
        $network = $this->getOpt('network') ?? false;
        
        if (!$address || !$reason || !$network) {
            $this->out('address, reason and network flags must be provided');
            return;
        }

        $success = $this->manager->add(
            (new Restriction())
                ->setAddress($address)
                ->setReason($reason)
                ->setNetwork($network)
        );

        if ($success) {
            $this->out("Successfully added entry for $address");
        }
    }

    /**
     * Delete a restriction.
     * @example
     * - php cli.php BlockchainRestrictions delete --address='0x000000000000000000000000000000000000dead'
     * @return void
     */
    public function delete()
    {
        $address = $this->getOpt('address') ?? false;
          
        if (!$address) {
            $this->out('address flags must be provided');
            return;
        }

        $this->manager->delete($address);

        $this->out("Successfully deleted entry for $address");
    }

    /**
     * Check whether a user has a restricted wallet connected.
     * WILL BAN IF A RESTRICTED WALLET IS FOUND.
     * @example
     * - php cli.php BlockchainRestrictions check --username='testuser'
     * @return void
     */
    public function check()
    {
        $username = $this->getOpt('username') ?? false;

        if (!$username) {
            $this->out('address and userGuid flags must be provided');
            return;
        }

        $user = $this->entitiesBuilder->getByUserByIndex($username);

        if (!$user) {
            $this->out("User: $username not found");
            return;
        }

        $address = $user->getEthWallet();
        if (!$address) {
            $this->out("User: $username has no wallet connected.");
        }
        try {
            $this->manager->gatekeeper($user->getEthWallet(), $user);
            $this->out("User: $username is not restricted");
        } catch (RestrictedException $e) {
            $this->out("User $username is restricted and has been banned");
        }
    }
}
