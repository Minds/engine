<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\GraphQL\Services;

use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\GraphQL\Repositories\TenantGuestModeFeedMySQLRepository;
use Minds\Core\Feeds\GraphQL\Types\ActivityEdge;
use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;

class TenantGuestModeFeedsService
{
    public function __construct(
        private readonly FeaturedEntityService $featuredEntityService,
        private readonly TenantGuestModeFeedMySQLRepository $tenantGuestModeFeedMySQLRepository,
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly Config $config,
        private readonly ACL $acl,
    ) {
    }

    /**
     * @return ActivityEdge[]
     * @throws NoTenantFoundException
     */
    public function getTenantGuestModeTopActivities(
        int $limit = 12,
        ?string &$loadAfter = null,
        ?string &$loadBefore = null,
        bool &$hasMore = false
    ): array {
        if ($this->config->get('tenant_id') === null) {
            throw new NoTenantFoundException();
        }

        $onlyFeaturedUsers = iterator_count($this->featuredEntityService->getAllFeaturedEntities(
            featuredEntityType: FeaturedEntityTypeEnum::USER
        )) > 0;

        $offset = $loadAfter ?? 0;

        $topActivities = $this->tenantGuestModeFeedMySQLRepository->getTopActivities(
            tenantId: $this->config->get('tenant_id'),
            limit: $limit,
            offset: (int) $offset,
            onlyFeaturedUsers: $onlyFeaturedUsers,
            hasMore: $hasMore,
            loadAfter: $loadAfter
        );

        $edges = [];
        foreach ($topActivities as $guid) {
            $activity = $this->fetchActivity($guid);
            if ($activity === null) {
                continue;
            }
            $cursor = $loadAfter;

            $edges[] = new ActivityEdge($activity, (string) $cursor ?? "", false);
        }

        return $edges;
    }

    /**
     * @param int $guid
     * @return Activity|null
     */
    private function fetchActivity(int $guid): ?Activity
    {
        $activity = $this->entitiesBuilder->single($guid);
        if (!($activity instanceof Activity)) {
            return null;
        }

        if (!$this->acl->read($activity)) {
            return null;
        }

        return $activity;
    }
}
