<?php
namespace Minds\Core\Authentication\Oidc\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class OidcUserRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_oidc_users';

    /**
     * Return a users guid (if available) from their oidc sub field
     */
    public function getUserGuidFromSub(string $sub, int $providerId): ?int
    {
        $values = [
            'sub' => $sub,
            'provider_id' => $providerId
        ];

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'user_guid'
            ])
            ->from(self::TABLE_NAME)
            ->where('sub', Operator::EQ, new RawExp(':sub'))
            ->where('oidc_provider_id', Operator::EQ, new RawExp(':provider_id'));

        if ($tenantId = $this->config->get('tenant_id')) {
            $query->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));
            $values['tenant_id'] = $tenantId;
        } else {
            $query->where('tenant_id', Operator::IS, null);
        }

        $stmt = $query->prepare();

        $stmt->execute($values);

        // If no records, there is no match
        if ($stmt->rowCount() === 0) {
            return null;
        }
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['user_guid'];
    }

    /**
     * Links a 'sub' field from oidc to a user_guid
     */
    public function linkSubToUserGuid(string $sub, int $providerId, int $userGuid): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'user_guid' => new RawExp(':user_guid'),
                'oidc_provider_id' => new RawExp(':provider_id'),
                'sub' => new RawExp(':sub'),
                'tenant_id' => new RawExp(':tenant_id'),
            ]);

        $stmt = $query->prepare();

        return $stmt->execute([
            'user_guid' => $userGuid,
            'provider_id' => $providerId,
            'sub' => $sub,
            'tenant_id' => $this->config->get('tenant_id'),
        ]);
    }
}
