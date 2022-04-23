<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Data\Cassandra\Thrift\Indexes;
use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;

/**
 * Assembles list of users with Youtube Sync enabled.
 */
class YoutubeSyncList implements SendGridListInterface
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Indexes $indexes = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->indexes = $indexes ?? Di::_()->get('Database\Cassandra\Indexes');
    }

    /**
     * Gets all contacts who have Youtube sync enabled.
     * @return SendGridContact[] - array of contacts with Twitter sync enabled.
     */
    public function getContacts(): iterable
    {
        $rows = $this->indexes->getRow("yt_channel:connected-users");

        foreach ($rows as $userGuid => $channelId) {
            $owner = $this->entitiesBuilder->single($userGuid);

            if (!$owner) {
                continue;
            }

            $contact = new SendGridContact();
            $contact
                ->setUserGuid($owner->getGuid())
                ->setUsername($owner->get('username'))
                ->setEmail($owner->getEmail())
                ->setHasYouTubeSync(true);

            if (!$contact->getEmail()) {
                continue;
            }

            yield $contact;
        }
    }
}
