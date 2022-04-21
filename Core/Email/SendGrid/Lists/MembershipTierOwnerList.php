<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;

/**
 * Assembles list of users who own membership tiers.
 */
class MembershipTierOwnerList implements SendGridListInterface
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Client $db = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Gets all contacts who have are owners of membership tiers.
     * @return SendGridContact[] - array of contacts who have are owners of membership tiers.
     */
    public function getContacts(): iterable
    {
        $cql = "SELECT DISTINCT entity_guid from wire_support_tier";
        $prepared = new Custom();
        $prepared->query($cql);
        $rows = $this->db->request($prepared);

        foreach ($rows as $row) {
            $owner = $this->entitiesBuilder->single($row['entity_guid']->__toString());

            if (!$owner) {
                continue;
            }

            $contact = new SendGridContact();
            $contact
                ->setUserGuid($owner->getGuid())
                ->setUsername($owner->get('username'))
                ->setEmail($owner->getEmail())
                ->setHasMembershipTier(true);

            if (!$contact->getEmail()) {
                continue;
            }

            yield $contact;
        }
    }
}
