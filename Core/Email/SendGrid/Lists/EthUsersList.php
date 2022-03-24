<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\Manager;
use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;

/**
 * Assembles list of users with a unique ETH address.
 */
class EthUsersList implements SendGridListInterface
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Manager $manager = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->manager = $manager ?? Di::_()->get('Blockchain\UniqueOnChain\Manager');
    }

    /**
     * Gets all contacts who have a unique ETH address.
     * @return SendGridContact[] - array of contacts with unique ETH address.
     */
    public function getContacts(): iterable
    {
        $users = $this->manager->getAll();
        foreach ($users as $user) {
            $owner = $this->entitiesBuilder->single($user->getUserGuid());

            if (!$owner) {
                continue;
            }

            $contact = new SendGridContact();
            $contact
                ->setUserGuid($owner->getGuid())
                ->setUsername($owner->get('username'))
                ->setEmail($owner->getEmail())
                ->setEthWallet($owner->getEthWallet());

            if (!$contact->getEmail()) {
                continue;
            }

            yield $contact;
        }
    }
}
