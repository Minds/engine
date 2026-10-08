<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\MultiTenant;

use Minds\Cli\Controller;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager as GroupsMembershipManager;
use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Core\MultiTenant\Services\FeaturedEntityAutoSubscribeService;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\MultiTenant\Types\FeaturedGroup;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\CliException;
use Minds\Exceptions\NotFoundException;
use Minds\Interfaces\CliControllerInterface;

/**
 * Backfills a network's existing users into its featured groups.
 * Featured groups are otherwise only joined at signup, or by the
 * featured-entity-sync consumer when a group is first featured.
 */
class FeaturedGroups extends Controller implements CliControllerInterface
{
    public function __construct(
        private ?MultiTenantBootService $bootService = null,
        private ?FeaturedEntityService $featuredEntityService = null,
        private ?FeaturedEntityAutoSubscribeService $autoSubscribeService = null,
        private ?TenantUsersService $tenantUsersService = null,
        private ?GroupsMembershipManager $membershipManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
    ) {
        Di::_()->get(Config::class)->set('min_log_level', 'info');
        $this->bootService ??= Di::_()->get(MultiTenantBootService::class);
        $this->featuredEntityService ??= Di::_()->get(FeaturedEntityService::class);
        $this->autoSubscribeService ??= Di::_()->get(FeaturedEntityAutoSubscribeService::class);
        $this->tenantUsersService ??= Di::_()->get(TenantUsersService::class);
        $this->membershipManager ??= Di::_()->get(GroupsMembershipManager::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    public function help($command = null)
    {
        $this->out("Usage:
            - php cli.php MultiTenant FeaturedGroups sync --tenantId=<tenantId> [--groupGuid=<guid>] [--apply]
            Without --apply this is a dry run and writes nothing.
            Without --groupGuid every auto-subscribe featured group on the network is synced.
        ");
    }

    public function exec(): void
    {
        $this->help();
    }

    /**
     * Joins every network user to the network's featured groups.
     * Banned users, banned group members and existing members/moderators/owners are skipped.
     * @return void
     */
    public function sync(): void
    {
        $tenantId = $this->getOpt('tenantId') ? (int) $this->getOpt('tenantId') : null;
        $groupGuid = $this->getOpt('groupGuid') ? (int) $this->getOpt('groupGuid') : null;
        $apply = (bool) $this->getOpt('apply');

        if (!$tenantId || $tenantId < 1) {
            throw new CliException('tenantId is a required parameter');
        }

        $this->bootService->bootFromTenantId($tenantId);

        $featuredGroups = [];
        foreach ($this->featuredEntityService->getAllFeaturedEntities($tenantId, FeaturedEntityTypeEnum::GROUP) as $featuredGroup) {
            if (!$featuredGroup instanceof FeaturedGroup || !$featuredGroup->autoSubscribe) {
                continue;
            }
            if ($groupGuid && $featuredGroup->entityGuid !== $groupGuid) {
                continue;
            }
            $featuredGroups[] = $featuredGroup;
        }

        if (!$featuredGroups) {
            throw new CliException('No matching auto-subscribe featured groups for this tenant');
        }

        $this->out(($apply ? 'APPLYING' : 'DRY RUN') . " tenant $tenantId");

        foreach ($featuredGroups as $featuredGroup) {
            $group = $this->entitiesBuilder->single($featuredGroup->entityGuid);
            if (!$group instanceof Group) {
                $this->out("Skipping {$featuredGroup->entityGuid}: group not found");
                continue;
            }

            $membersBefore = $this->membershipManager->getMembersCount($group);
            $counts = ['users' => 0, 'already_in' => 0, 'banned' => 0, 'would_add' => 0, 'added' => 0, 'errors' => 0];

            foreach ($this->tenantUsersService->getUsers(tenantId: $tenantId) as $user) {
                $counts['users']++;
                try {
                    if ($user->isBanned()) {
                        $counts['banned']++;
                        continue;
                    }

                    $status = $this->getStatus($group, $user);
                    if ($status !== 'join') {
                        $counts[$status]++;
                        continue;
                    }

                    $counts['would_add']++;

                    if ($apply && $this->autoSubscribeService->handleFeaturedGroup($featuredGroup, $user)) {
                        $counts['added']++;
                    }
                } catch (\Exception $e) {
                    $counts['errors']++;
                    $this->out("Error on user {$user->getGuid()}: {$e->getMessage()}");
                }
            }

            $this->out(json_encode([
                'group_guid' => (string) $featuredGroup->entityGuid,
                'group_name' => $group->getName(),
                'members_before' => $membersBefore,
                ...$counts,
                'members_now' => $this->membershipManager->getMembersCount($group),
            ]));
        }
    }

    /**
     * @return string 'join', 'already_in' or 'banned'.
     */
    private function getStatus(Group $group, User $user): string
    {
        try {
            $membership = $this->membershipManager->getMembership($group, $user);
        } catch (NotFoundException $e) {
            return 'join';
        }

        if ($membership->isBanned()) {
            return 'banned';
        }

        return $membership->isAwaiting() ? 'join' : 'already_in';
    }
}
