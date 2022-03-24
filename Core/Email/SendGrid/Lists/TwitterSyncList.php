<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\TwitterSync\Repository;

/**
 * Assembles list of users with TwitterSync enabled.
 */
class TwitterSyncList implements SendGridListInterface
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Repository $twitterSyncRepository = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->twitterSyncRepository = $twitterSyncRepository ?? Di::_()->get('Feeds\TwitterSync\Repository');
    }

    /**
     * Gets all contacts who have Twitter sync enabled.
     * @return SendGridContact[] - array of contacts with Twitter sync enabled.
     */
    public function getContacts(): iterable
    {
        foreach ($this->twitterSyncRepository->getList() as $connectedAccount) {
            $owner = $this->entitiesBuilder->single($connectedAccount->getUserGuid());

            if (!$owner) {
                continue;
            }

            $contact = new SendGridContact();
            $contact
                ->setUserGuid($owner->getGuid())
                ->setUsername($owner->get('username'))
                ->setEmail($owner->getEmail())
                ->setHasTwitterSync(true);

            if (!$contact->getEmail()) {
                continue;
            }

            yield $contact;
        }
    }
}
