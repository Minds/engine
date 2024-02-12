<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Exceptions\ServerErrorException;
use PDOException;
use Selective\Database\RawExp;

class PaywalledEntitiesRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_site_membership_entities';

    /**
     * Map an entity guid with the membership guids
     */
    public function mapMembershipsToEntity(
        int   $entityGuid,
        array $membershipGuids
    ): bool {

        $this->beginTransaction();

        foreach ($membershipGuids as $membershipGuid) {
            $stmt = $this->mysqlClientWriterHandler->insert()
                ->into(self::TABLE_NAME)
                ->set([
                    'tenant_id' => $this->config->get('tenant_id') ?? -1,
                    'entity_guid' => new RawExp(':entity_guid'),
                    'membership_guid' => new RawExp(':membership_guid'),
                ])
                ->prepare();

            try {
                $success = $stmt->execute([
                    'entity_guid' => $entityGuid,
                    'membership_guid' => $membershipGuid,
                ]);
                if (!$success) {
                    return false;
                }
            } catch (PDOException $e) {
                $this->rollbackTransaction();
                throw new ServerErrorException(
                    message: 'Failed to save the site membership map',
                    previous: $e
                );
            }
        }

        // We do not commit as we are expecting the MySQL repository to end the transaction
        return true;
    }


}
