<?php
namespace Minds\Core\Search\Controllers;

use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use Minds\Common\Access;
use Minds\Core\ActivityPub\Services\ProcessActorService;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\GraphQL\Types\BoostEdge;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic;
use Minds\Core\Feeds\Elastic\V2\Enums\SeenEntitiesFilterStrategyEnum;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Core\Feeds\GraphQL\Types\ActivityEdge;
use Minds\Core\Feeds\GraphQL\Types\PublisherRecsConnection;
use Minds\Core\Feeds\GraphQL\Types\PublisherRecsEdge;
use Minds\Core\Feeds\GraphQL\Types\UserEdge;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Groups\V2\GraphQL\Types\GroupEdge;
use Minds\Core\Search\Enums\SearchFilterEnum;
use Minds\Core\Search\Enums\SearchMediaTypeEnum;
use Minds\Core\Search\Enums\SearchNsfwEnum;
use Minds\Core\Search\Search;
use Minds\Core\Search\Types\SearchResultsConnection;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Query;

class SearchController
{
    public function __construct(
        protected Elastic\V2\Manager $elasticFeedsManager,
        protected Search $search,
        protected EntitiesBuilder $entitiesBuilder,
        protected BoostManager $boostManager,
        protected ProcessActorService $processActorService,
    ) {
    }

    /**
     * @param SearchNsfwEnum[]|null $nsfw
     */
    #[Query]
    public function search(
        ResolveInfo $gqlResolveInfo,
        #[InjectUser()] $loggedInUser,
        string $query,
        SearchFilterEnum $filter,
        SearchMediaTypeEnum $mediaType,
        ?array $nsfw = [],
        ?int $first = 12,
        ?string $after = null,
        ?int $last = null,
        ?string $before = null,
    ): SearchResultsConnection {
        if ($first && $last) {
            throw new UserError("first and last supplied, can only paginate in one direction");
        }

        if ($after && $before) {
            throw new UserError("after and before supplied, can only provide one cursor");
        }

        $loadAfter = $after;
        $loadBefore = $before;

        $limit = min($first ?: $last, 12); // MAX 12

        // Remove # hashtag symbol
        $query = str_replace('#', '', $query);

        $latestQueryOpts = new QueryOpts(
            user: $loggedInUser,
            limit: $limit,
            query: $query,
            accessId: Access::PUBLIC,
            mediaTypeEnum: SearchMediaTypeEnum::toMediaTypeEnum($mediaType),
            nsfw: $this->nsfwEnumsToIntArray($nsfw ?: []),
        );

        $topQueryOpts = new QueryOpts(
            user: $loggedInUser,
            limit: $limit,
            query: $query,
            accessId: Access::PUBLIC,
            mediaTypeEnum: SearchMediaTypeEnum::toMediaTypeEnum($mediaType),
            nsfw: $this->nsfwEnumsToIntArray($nsfw),
            seenEntitiesFilterStrategy: SeenEntitiesFilterStrategyEnum::DEMOTE,
        );

        /**
         * Count query
         */
        $gqlFieldSelection = $gqlResolveInfo->getFieldSelection();
        if (isset($gqlFieldSelection['count'])) {
            if (isset($gqlFieldSelection['edges'])) {
                throw new UserError("You can not request both a 'count' and edges in the same request.");
            }

            if (!($after || $before)) {
                throw new UserError("You must provide a cursor in order to perform a count query");
            }

            $count = match ($filter) {
                SearchFilterEnum::LATEST => $this->elasticFeedsManager->getLatestCount(
                    queryOpts: $latestQueryOpts,
                    loadAfter: $loadAfter,
                    loadBefore: $loadBefore,
                    hasMore: $hasMore,
                ),
                SearchFilterEnum::TOP => $this->elasticFeedsManager->getTopCount(
                    queryOpts: $topQueryOpts,
                    loadAfter: $loadAfter,
                    loadBefore: $loadBefore,
                    hasMore: $hasMore,
                ),
                default => 0
            };

            $connection = new SearchResultsConnection();

            $connection->setCount($count);

            $pageInfo = new PageInfo(
                hasNextPage: true,
                hasPreviousPage: true,
                startCursor: $loadBefore,
                endCursor: $loadAfter,
            );

            $connection->setPageInfo($pageInfo);

            return $connection;
        }

        /**
         * Main search query
         */
        $hasMore = false;

        $entities = match ($filter) {
            SearchFilterEnum::LATEST => $this->elasticFeedsManager->getLatest(
                queryOpts: $latestQueryOpts,
                loadAfter: $loadAfter,
                loadBefore: $loadBefore,
                hasMore: $hasMore,
            ),
            SearchFilterEnum::TOP => $this->elasticFeedsManager->getTop(
                queryOpts: $topQueryOpts,
                loadAfter: $loadAfter,
                loadBefore: $loadBefore,
                hasMore: $hasMore,
            ),
            SearchFilterEnum::USER => $this->getPublisherSearch(
                type: 'user',
                query: $query,
                limit: $limit,
                loadAfter: $loadAfter,
                loadBefore: $loadBefore,
                hasMore: $hasMore,
            ),
            SearchFilterEnum::GROUP => $this->getPublisherSearch(
                type: 'group',
                query: $query,
                limit: $limit,
                loadAfter: $loadAfter,
                loadBefore: $loadBefore,
                hasMore: $hasMore,
            ),
            default => throw new UserError("Can not support supplied filter"),
        };

        $boosts = [];
        if ($loggedInUser) {
            // If not on latest or top, we request ZERO boosts as the clients dont support yet
            $boosts = $this->buildBoosts(
                loggedInUser: $loggedInUser,
                limit: in_array($filter, [ SearchFilterEnum::TOP, SearchFilterEnum::LATEST ], true) ? 3 : 0,
                targetLocation: in_array($filter, [ SearchFilterEnum::TOP, SearchFilterEnum::LATEST ], true) ? BoostTargetLocation::NEWSFEED : BoostTargetLocation::SIDEBAR,
            );
        }

        $edges = [];

        if ($filter === SearchFilterEnum::USER) {
            try {
                $user = $this->processActorService
                    ->withUsername($query)
                    ->process();
                $edges[] = new UserEdge($user, "");
            } catch (\Exception $e) {
            }
        }

        /**
         * If top filter and first slot, show matched groups
         */
        if ($filter === SearchFilterEnum::TOP) {
            $edges[] = $this->buildMatchedGroups(query: $query, limit: 3, edgeCursor: '');
        }

        foreach ($entities as $i => $entity) {
            $cursor = $loadAfter;

            if (
                in_array($i, [
                    1, // 2nd slot
                    4, // after channel recs
                    6, // below higlights
                ], true) &&
                count($boosts)
            ) {
                $boost = array_shift($boosts);
                if ($boost) {
                    $edges[] = new BoostEdge($boost, $cursor);
                }
            }

            if ($filter === SearchFilterEnum::USER && $user?->getGuid() === $entity->getGuid()) {
                continue;
            }

            $entityEdge = match (get_class($entity)) {
                Activity::class => new ActivityEdge($entity, $cursor, false),
                User::class => new UserEdge($entity, $cursor ?: ''),
                Group::class => new GroupEdge($entity, $cursor ?: ''),
                default => null,
            };

            if ($entityEdge) {
                $edges[] = $entityEdge;
            }
        }
    
        $connection = new SearchResultsConnection();
        $connection->setEdges($edges);

        $pageInfo = new PageInfo(
            hasPreviousPage: $filter === SearchFilterEnum::LATEST ? true : false,
            hasNextPage: $hasMore,
            startCursor: $loadBefore,
            endCursor: $loadAfter,
        );

        $connection->setPageInfo($pageInfo);
        
        return $connection;
    }

    /**
     * @return (User|Group)[]
     */
    private function getPublisherSearch(
        string $type,
        string $query,
        int $limit,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null
    ): array {
        $guids = array_map(fn ($doc) => $doc['guid'], $this->search->suggest($type, $query, $limit));

        $entities = array_filter(array_map(fn ($guid) => $this->entitiesBuilder->single($guid), $guids));

        $loadAfter = base64_encode((string) count($entities));
        $loadBefore = base64_encode((string) 0);

        $hasMore = false;

        return array_values($entities);
    }

    /**
     * Finds groups matching the search term
     */
    private function buildMatchedGroups(string $query, int $limit = 3, string $edgeCursor = ''): PublisherRecsEdge
    {
        $result = $this->getPublisherSearch(
            type: 'group',
            query: $query,
            limit: $limit,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
            hasMore: $hasMore,
        );

        $edges = [];

        foreach ($result as $i => $entity) {
            $cursor = base64_encode($i);
            if ($entity instanceof Group) {
                $edges[] = new GroupEdge($entity, $loadAfter);
            }
        }

        $pageInfo = new PageInfo(
            hasPreviousPage: false,
            hasNextPage: $hasMore,
            startCursor: $loadBefore,
            endCursor: $loadAfter,
        );

        $connection = new PublisherRecsConnection();
        $connection->setEdges($edges);
        $connection->setPageInfo($pageInfo);

        return new PublisherRecsEdge($connection, $edgeCursor);
    }

    /**
     * @return Boost[]
     */
    protected function buildBoosts(
        User $loggedInUser,
        int $limit = 3,
        int $targetLocation = BoostTargetLocation::NEWSFEED,
    ): array {
        if (!$this->boostManager->shouldShowBoosts($loggedInUser)) {
            return [];
        }

        $boosts = $this->boostManager->getBoostFeed(
            limit: $limit,
            targetStatus: BoostStatus::APPROVED,
            orderByRanking: true,
            targetAudience: $loggedInUser->getBoostRating(),
            targetLocation: $targetLocation,
            castToFeedSyncEntities: false,
        );

        return $boosts->toArray();
    }

    /**
     * @param SearchNsfwEnum[]
     * @return int[]
     */
    private function nsfwEnumsToIntArray(array $nsfw): array
    {
        return array_map(function ($n) {
            return $n->value;
        }, $nsfw);
    }
}
