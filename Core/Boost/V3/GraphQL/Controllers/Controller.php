<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\GraphQL\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\GraphQL\Types\BoostEdge;
use Minds\Core\Boost\V3\GraphQL\Types\BoostsConnection;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Log\Logger;
use Minds\Core\Boost\V3\Manager;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Boosts GraphQL Controller.
 */
class Controller
{
    public function __construct(
        private readonly Manager $manager,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Gets Boosts.
     * @param int $targetLocation - target location of the requested Boosts.
     * @param int|null $targetAudience - target audience of the requested Boosts.
     * @param string|null $servedByGuid - guid of the entity that is serving the Boosts.
     * @param string|null $source - source of the requested Boosts.
     * @param int|null $first - number of Boosts to return.
     * @param int|null $after - cursor to start from.
     * @param int|null $last - number of Boosts to return.
     * @param int|null $before - cursor to start from.
     * @return BoostsConnection
     */
    #[Query]
    #[Logged]
    public function boosts(
        int $targetLocation = BoostTargetLocation::NEWSFEED,
        ?int $targetAudience = null,
        ?string $servedByGuid = null,
        ?string $source = null,
        ?int $first = null,
        ?int $after = null,
        ?int $last = null,
        ?int $before = null,
        #[InjectUser] User $loggedInUser = null // Do not add in docblock as it will break GraphQL
    ): BoostsConnection {
        if ($first && $last) {
            throw new UserError("first and last supplied, can only paginate in one direction");
        }

        if ($after && $before) {
            throw new UserError("after and before supplied, can only provide one cursor");
        }

        $loadAfter = $after;
        $loadBefore = $before;
        $limit = min($first ?? $last, 12); // MAX 12

        if (!$targetAudience) {
            $targetAudience = (
                $loggedInUser->getBoostRating() !== BoostTargetAudiences::CONTROVERSIAL ?
                    BoostTargetAudiences::SAFE :
                    BoostTargetAudiences::CONTROVERSIAL
            );
        }

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoostFeed(
                limit: (int) $limit,
                offset: (int) $after,
                targetStatus: BoostStatus::APPROVED,
                orderByRanking: true,
                targetAudience: (int) $targetAudience,
                targetLocation: (int) $targetLocation ?: null,
                servedByGuid: $servedByGuid,
                source: $source,
                castToFeedSyncEntities: false
            );

        $edges = [];
        foreach ($boosts as $boost) {
            $edges[] = new BoostEdge($boost, (string) $after ?? '');
        }

        $connection = (new BoostsConnection())
            ->setEdges($edges)
            ->setPageInfo(new PageInfo(
                hasNextPage: (bool) $boosts->getPagingToken(),
                hasPreviousPage: $after && $loadBefore,
                startCursor: ($after && $loadBefore) ? $loadBefore : null,
                endCursor: (string) ($loadAfter + $limit),
            ));

        return $connection;
    }
}
