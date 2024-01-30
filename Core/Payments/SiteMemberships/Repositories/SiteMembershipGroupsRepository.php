<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Exceptions\ServerErrorException;
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
    ): bool
    {
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
