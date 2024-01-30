<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Exceptions\ServerErrorException;
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
    ): bool
    {
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
                    'group_guid' => $siteMembershipRole->id,
                ]);
            } catch (Exception $e) {
                throw new ServerErrorException(
                    message: 'Failed to store site membership groups',
                    previous: $e
                );
            }
        }
        return true;
    }
}
