<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipGroupsFoundException;
use Minds\Exceptions\ServerErrorException;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class SiteMembershipGroupsRepository extends AbstractRepository
{
    /**
     * @param int $siteMembershipGuid
     * @param GroupNode[] $siteMembershipGroups
     * @return bool
     * @throws ServerErrorException
     */
    public function storeSiteMembershipGroups(
        int   $siteMembershipGuid,
        array $siteMembershipGroups
    ): bool {
        foreach ($siteMembershipGroups as $siteMembershipGroupNode) {
            $stmt = $this->mysqlClientWriterHandler->insert()
                ->into('minds_site_membership_tiers_group_assignments')
                ->set([
                    'tenant_id' => $this->config->get('tenant_id') ?? -1,
                    'membership_tier_guid' => $siteMembershipGuid,
                    'group_guid' => new RawExp(':group_guid'),
                ])
                ->prepare();

            try {
                $stmt->execute([
                    'group_guid' => $siteMembershipGroupNode->getEntity()->getGuid(),
                ]);
            } catch (PDOException $e) {
                throw new ServerErrorException(
                    message: 'Failed to store site membership groups',
                    previous: $e
                );
            }
        }
        return true;
    }

    /**
     * @param int $siteMembershipGuid
     * @return array|null
     * @throws NoSiteMembershipGroupsFoundException
     * @throws ServerErrorException
     */
    public function getSiteMembershipGroups(int $siteMembershipGuid): ?array
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from('minds_site_membership_tiers_group_assignments')
            ->columns([
                'group_guid',
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('membership_tier_guid', Operator::EQ, $siteMembershipGuid)
            ->prepare();

        try {
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new NoSiteMembershipGroupsFoundException();
            }
            $groupGuids = [];
            foreach ($stmt->fetchAll() as $group) {
                $groupGuids[] = $group['group_guid'];
            }
            return $groupGuids;
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to get site membership groups',
                previous: $e
            );
        }
    }

    /**
     * @param int $siteMembershipGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteSiteMembershipGroups(int $siteMembershipGuid): bool
    {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from('minds_site_membership_tiers_group_assignments')
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('membership_tier_guid', Operator::EQ, $siteMembershipGuid)
            ->prepare();

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to delete site membership groups',
                previous: $e
            );
        }
    }
}
