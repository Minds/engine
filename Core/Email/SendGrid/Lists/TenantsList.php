<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PDO;

/**
 * Users who have a tenant site (newest only)
 */
class TenantsList implements SendGridListInterface
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
        $statement = "SELECT t.tenant_id, t.owner_guid, d.domain, t.trial_start_timestamp, t.plan, t.created_timestamp
        FROM (SELECT ROW_NUMBER() OVER (
            PARTITION BY owner_guid
            ORDER BY created_timestamp
            ) row_num,
                     tenant_id,
                     owner_guid,
                     plan,
                     trial_start_timestamp,
                     created_timestamp
              FROM minds_tenants) t
                 LEFT JOIN minds_tenants_domain_details d ON t.tenant_id = d.tenant_id
        WHERE row_num = 1";


        $stmt = $this->mysqlClient->getConnection(MySQLConnectionEnum::REPLICA)->prepare($statement);
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $user = $this->entitiesBuilder->single($row['owner_guid']);

            if (!$user instanceof User) {
                continue;
            }

            $tenantDomain = $row['domain'] ?: md5($row['tenant_id']) . '.networks.minds.com';

            $contact = new SendGridContact();
            $contact
                ->setUser($user)
                ->setUserGuid($user->getGuid())
                ->setUsername($user->getUsername())
                ->setEmail($user->getEmail())
                ->set('tenant_id', $row['tenant_id'])
                ->set('tenant_plan', $row['plan'])
                ->set('tenant_trial', $row['trial_start_timestamp'] ? 1 : 0)
                ->set('tenant_domain', $tenantDomain)
                ->set('tenant_created_ts', date('c', strtotime($row['created_timestamp'])));

            yield $contact;
        }

        return;
    }
}
