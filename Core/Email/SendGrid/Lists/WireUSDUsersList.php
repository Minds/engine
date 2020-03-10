<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Core\Email\SendGrid\SendGridContact;

class WireUSDUsersList implements SendGridListInterface
{
    /** @var Scroll */
    protected $scroll;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct($scroll = null, $entitiesBuilder = null)
    {
        $this->scroll = $scroll ?? Di::_()->get('Database\Cassandra\Cql\Scroll');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @return SendGridContact[]
     */
    public function getContacts(): iterable
    {
        $prepared = new Prepared\Custom();
        $prepared->query("SELECT * FROM wire WHERE method IN ('money', 'usd') ALLOW FILTERING");

        $usersToLastWire = [];

        foreach ($this->scroll->request($prepared) as $row) {
            $userGuid = (string) $row['sender_guid'];
            $timestamp = $row['timestamp']->time();

            if (isset($usersToLastWire[$userGuid]) && $usersToLastWire[$userGuid] < $timestamp) {
                continue;
            }
            $usersToLastWire[$userGuid] = $timestamp;

            $user = $this->entitiesBuilder->single($userGuid);

            if (!$user) {
                continue;
            }
            $contact = new SendGridContact();
            $contact
                ->setUserGuid($user->getGuid())
                ->setUsername($user->get('username'))
                ->setEmail($user->getEmail())
                ->setLastWire($timestamp);

            if (!$contact->getEmail()) {
                continue;
            }
            yield $contact;
        }
    }
}
