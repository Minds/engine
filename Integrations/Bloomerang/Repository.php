<?php
namespace Minds\Integrations\Bloomerang;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\GraphQL\Types\KeyValuePair;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class Repository extends AbstractRepository
{
    const TABLE_NAME = 'minds_bloomerang_group_id_to_site_membership_guids';

    /**
     * Returns a map of a Bloomerang group id to a minds site membership guid
     * @return KeyValuePair[]
     */
    public function getGroupIdToSiteMembershipGuidMap(): array
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        $stmt = $query->prepare();

        $stmt->execute([
            'tenant_id' => $this->config->get('tenant_id'),
        ]);

        $map = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[] = new KeyValuePair($row['bloomerang_group_id'], $row['site_membership_guid']);
        }

        return $map;
    }
}
