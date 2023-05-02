<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PDO;

/**
 * User last boost
 */
class BoostedV3List implements SendGridListInterface
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Client $mysqlClient = null,
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->mysqlClient ??= Di::_()->get("Database\MySQL\Client");
    }

    /**
     * Gets users who have been active in last 30 days.
     * @return SendGridContact[] array of contacts who have been active in last 30 days.
     */
    public function getContacts(): iterable
    {
        $statement = "SELECT owner_guid, payment_method, count(*) as num_boosts, max(completed_timestamp) as last_boosted_ts
            FROM boosts
            WHERE completed_timestamp > :fromTs
            AND status IN (9)
            GROUP BY 1,2";
        $values = [
            'fromTs' => date('c', strtotime('90 days ago')),
        ];

        $stmt = $this->mysqlClient->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);
        $stmt->execute($values);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $user = $this->entitiesBuilder->single($row['owner_guid']);

            if (!$user instanceof User) {
                continue;
            }

            $boostPaymentMethod = match ((int) $row['payment_method']) {
                BoostPaymentMethod::CASH => 'cash',
                BoostPaymentMethod::OFFCHAIN_TOKENS => 'tokens',
                BoostPaymentMethod::ONCHAIN_TOKENS => 'onchain'
            };

            $contact = new SendGridContact();
            $contact
                ->setUser($user)
                ->setUserGuid($user->getGuid())
                ->setUsername($user->getUsername())
                ->setEmail($user->getEmail())
                ->set('last_boost_' . $boostPaymentMethod, $row['last_boosted_ts']);

            yield $contact;
        }

        return;
    }
}
