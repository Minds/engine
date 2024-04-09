<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipRolesFoundException;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Exceptions\ServerErrorException;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class SiteMembershipRolesRepository extends AbstractRepository
{
    /**
     * @param int $siteMembershipGuid
     * @param Role[] $siteMembershipRoles
     * @return bool
     * @throws ServerErrorException
     */
    public function storeSiteMembershipRoles(
        int   $siteMembershipGuid,
        array $siteMembershipRoles
    ): bool {
        foreach ($siteMembershipRoles as $siteMembershipRole) {
            $stmt = $this->mysqlClientWriterHandler->insert()
                ->into('minds_site_membership_tiers_role_assignments')
                ->set([
                    'tenant_id' => $this->config->get('tenant_id') ?? -1,
                    'membership_tier_guid' => $siteMembershipGuid,
                    'role_id' => new RawExp(':role_id'),
                ])
                ->prepare();

            try {
                $stmt->execute([
                    'role_id' => $siteMembershipRole->id,
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
     * @throws NoSiteMembershipRolesFoundException
     * @throws ServerErrorException
     */
    public function getSiteMembershipRoles(int $siteMembershipGuid): ?array
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from('minds_site_membership_tiers_role_assignments')
            ->columns([
                'role_id',
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('membership_tier_guid', Operator::EQ, $siteMembershipGuid)
            ->prepare();

        try {
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new NoSiteMembershipRolesFoundException();
            }
            $roles = [];
            foreach ($stmt->fetchAll() as $role) {
                $roles[] = $role['role_id'];
            }
            return $roles;
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to get site membership roles',
                previous: $e
            );
        }
    }

    /**
     * @param int $siteMembershipGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteSiteMembershipRoles(int $siteMembershipGuid): bool
    {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from('minds_site_membership_tiers_role_assignments')
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('membership_tier_guid', Operator::EQ, $siteMembershipGuid)
            ->prepare();

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to delete site membership roles',
                previous: $e
            );
        }
    }
}
