<?php
namespace Minds\Core\Feeds\GraphQL\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\GraphQL\Types\BoostEdge;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\GraphQL\Types;
use Minds\Core\Feeds\GraphQL\Types\ActivityEdge;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Session;
use Minds\Core\FeedNotices;
use Minds\Core\FeedNotices\GraphQL\Types\FeedNoticeEdge;
use Minds\Core\Feeds\GraphQL\Types\ActivityNode;
use Minds\Core\Feeds\GraphQL\Types\FeedHighlightsConnection;
use Minds\Core\Feeds\GraphQL\Types\FeedHighlightsEdge;
use Minds\Core\Feeds\GraphQL\Types\NewsfeedConnection;
use Minds\Core\Feeds\GraphQL\Types\PublisherRecsConnection;
use Minds\Core\Feeds\GraphQL\Types\PublisherRecsEdge;
use Minds\Core\Feeds\GraphQL\Types\UserEdge;
use Minds\Core\Recommendations\Algorithms\SuggestedChannels\SuggestedChannelsRecommendationsAlgorithm;
use Minds\Core\Recommendations\Injectors\BoostSuggestionInjector;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Query;

class NewsfeedController
{
    public function __construct(
        protected FeedsManager $feedsManager,
        protected EntitiesBuilder $entitiesBuilder,
        protected FeedNotices\Manager $feedNoticesManager,
        protected BoostManager $boostManager,
        protected SuggestedChannelsRecommendationsAlgorithm $suggestedChannelsRecommendationsAlgorithm,
        protected BoostSuggestionInjector $boostSuggestionInjector,
        protected ExperimentsManager $experimentsManager,
    ) {
    }

    /**
     * @param string[]|null $inFeedNoticesDelivered
     */
    #[Query]
    public function getNewsfeed(
        string $algorithm,
        ?int $first = null,
        ?string $after = null,
        ?int $last = null,
        ?string $before = null,
        ?array $inFeedNoticesDelivered = [],
    ): NewsfeedConnection {
        if ($first && $last) {
            throw new UserError("first and last supplied, can only paginate in one direction");
        }

        if ($after && $before) {
            throw new UserError("after and before supplied, can only provide one cursor");
        }

        $loadAfter = $after;
        $loadBefore = $before;

        /**
         * The limit to use
         */
        $limit = min($first ?: $last, 12); // MAX 12

        $loggedInUser =  Session::getLoggedInUser();

        if (!$loggedInUser) {
            throw new UserError("You must be logged in", 403);
        }

        $edges = [];

        switch ($algorithm) {
            case "latest":
                $activities = $this->feedsManager->getLatestSubscribed(
                    user: $loggedInUser,
                    limit: $limit,
                    hasMore: $hasMore,
                    loadAfter: $loadAfter,
                    loadBefore: $loadBefore,
                );
                break;
            case "groups":
                $activities = $this->feedsManager->getLatestGroups(
                    user: $loggedInUser,
                    limit: $limit,
                    hasMore: $hasMore,
                    loadAfter: $loadAfter,
                    loadBefore: $loadBefore,
                );
                break;
            case "top":
                $activities = $this->feedsManager->getTopSubscribed(
                    user: $loggedInUser,
                    limit: $limit,
                    hasMore: $hasMore,
                    loadAfter: $loadAfter,
                    loadBefore: $loadBefore,
                );
                break;
            case "for-you":
                $activities = $this->feedsManager->getClusteredRecs(
                    user: $loggedInUser,
                    limit: $limit,
                    hasMore: $hasMore,
                    loadAfter: $loadAfter,
                    loadBefore: $loadBefore,
                );
                break;
            default:
                throw new UserError("Invalid algorithm supplied");
        }

        // Build the boosts
        $isBoostRotatorRemovedExpirementOn = $this->experimentsManager->isOn('minds-4105-remove-rotator');
        $boosts = $this->buildBoosts(
            loggedInUser: $loggedInUser,
            limit: $isBoostRotatorRemovedExpirementOn ? 3 : 1,
        );

        foreach ($activities as $i => $activity) {
            $cursor = $loadAfter;
    
            // Do not return more than the limit
            if (count($edges) >= $limit) {
                break;
            }

            if ($i === 0) { // Priority notice is always at the top
                $priorityNotices = $this->buildInFeedNotices(
                    loggedInUser: $loggedInUser,
                    location: ($after ||$before) ? 'inline' : 'top',
                    limit: 1,
                    cursor: $cursor,
                    inFeedNoticesDelivered: $inFeedNoticesDelivered
                );
                if ($priorityNotices && isset($priorityNotices[0])) {
                    $edges[] = $priorityNotices[0];
                    $inFeedNoticesDelivered[] = $priorityNotices[0]->getNode()->getKey();
                }
            }

            /**
             * Show top highlights on the first page load
             */
            if ($i === 6 && $algorithm === 'latest' && !($after || $before)) {
                $topHighlightsEdge = $this->buildFeedHighlights($loggedInUser, $cursor);
                if ($topHighlightsEdge) {
                    $edges[] = $topHighlightsEdge;
                }
            }

            if ($i === 6) { // Show in the 6th spot
                $inlineNotice = $this->buildInFeedNotices(
                    loggedInUser: $loggedInUser,
                    location: 'inline',
                    limit: 1,
                    cursor: $cursor,
                    inFeedNoticesDelivered: $inFeedNoticesDelivered,
                );
                if ($inlineNotice && isset($inlineNotice[0])) {
                    $edges[] = $inlineNotice[0];
                }
            }

            if ($i === 3 && !($after || $before)) {
                $channelRecs = $this->buildChannelRecs($loggedInUser, $cursor);
                if ($channelRecs) {
                    $edges[] = $channelRecs;
                }
            }

            /**
             * Show boosts depending on if the experiment to remove the rotator is enabled
             */
            if (
                $isBoostRotatorRemovedExpirementOn &&
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
            } elseif (
                !$isBoostRotatorRemovedExpirementOn &&
                count($boosts) &&
                $i == 3
            ) {
                $boost = $boosts[0];
                if ($boost) {
                    $edges[] = new BoostEdge($boost, $cursor);
                }
            }

            $edges[] = new ActivityEdge($activity, $cursor);
        }

        $pageInfo = new Types\PageInfo(
            hasPreviousPage: $algorithm === 'latest' || ($after && $loadBefore) ? true : false, // Always will be newer data on latest or if we are paging forward
            hasNextPage: $hasMore,
            startCursor: $loadBefore,
            endCursor: $loadAfter,
        );

        $connection = new NewsfeedConnection();
        $connection->setEdges($edges);
        $connection->setPageInfo($pageInfo);

        return $connection;
    }

    #[Query]
    public function getActivity(
        string $guid
    ): ActivityNode {
        $activity = $this->entitiesBuilder->single($guid);
        return new ActivityNode($activity);
    }

    /**
     * @return Boost[]
     */
    protected function buildBoosts(
        User $loggedInUser,
        int $limit = 3
    ): array {
        if ($loggedInUser->disabled_boost && $loggedInUser->isPlus()) {
            return [];
        }

        $boosts = $this->boostManager->getBoostFeed(
            limit: $limit,
            targetStatus: BoostStatus::APPROVED,
            orderByRanking: true,
            targetAudience: $loggedInUser->getBoostRating(),
            targetLocation: BoostTargetLocation::NEWSFEED,
            castToFeedSyncEntities: false,
        );

        return $boosts->toArray();
    }

    /**
     * Add in feed notices
     * @return FeedNoticeEdge[]
     */
    protected function buildInFeedNotices(
        User $loggedInUser,
        string $cursor,
        array $inFeedNoticesDelivered,
        string $location = 'inline',
        int $limit = 1
    ): array {
        $edges = [];

        $feedNotices = $this->feedNoticesManager->getNotices($loggedInUser);
        $i = 0;
        foreach ($feedNotices as $feedNotice) {
            try {
                if (
                    in_array($feedNotice->getKey(), $inFeedNoticesDelivered, true)
                    || $feedNotice->getLocation() !== $location
                    || !$feedNotice->shouldShow($loggedInUser)
                ) {
                    continue;
                }
            } catch (\Exception $e) {
            }
            $edges[] = new FeedNoticeEdge($feedNotice, $cursor);
            if (++$i >= $limit) {
                break;
            }
        }

        return $edges;
    }

    /**
     * Will attempt to build feed highlights, if there any available
     * @return FeedHighlightsEdge|null
     */
    protected function buildFeedHighlights(User $loggedInUser, string $cursor): ?FeedHighlightsEdge
    {
        $activities = $this->feedsManager->getTopSubscribed(
            user: $loggedInUser,
            limit: 3,
            hasMore: $hasMore,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
        );

        $edges = [];

        foreach ($activities as $activity) {
            $cursor = $loadAfter;
            $edges[] = new ActivityEdge($activity, $cursor);
        }

        if (empty($edges)) {
            return null; // Do not return FeedHighlightsEdge if its empty
        }

        $pageInfo = new Types\PageInfo(
            hasPreviousPage: false,
            hasNextPage: $hasMore,
            startCursor: $loadBefore,
            endCursor: $loadAfter,
        );

        $connection = new FeedHighlightsConnection();
        $connection->setEdges($edges);
        $connection->setPageInfo($pageInfo);

        return new FeedHighlightsEdge($connection, $cursor);
    }

    /**
     * Builds out channel recommendations, and includes a boost slot too
     * @return PublisherRecsEdge
     */
    protected function buildChannelRecs(User $loggedInUser, string $cursor): PublisherRecsEdge
    {
        $result = $this->suggestedChannelsRecommendationsAlgorithm
            ->setUser($loggedInUser)
            ->getRecommendations([
                'limit' => 3
            ]);

        // Inject a boosted channel (if not plus and disabled)
        if (!($loggedInUser->disabled_boost && $loggedInUser->isPlus())) {
            $result = $this->boostSuggestionInjector->inject(
                response: $result,
                targetUser: $loggedInUser,
                index: 1
            );
        }

        $edges = [ ];

        foreach ($result as $i => $suggestion) {
            $cursor = base64_encode($i);
            $entity = $suggestion->getEntity();
            if ($entity instanceof User) {
                $edges[] = new UserEdge($entity, $cursor);
            }
            if ($entity->boost instanceof Boost) {
                $edges[] = new BoostEdge($entity->boost, $cursor);
            }
        }

        // Inject a boosted channel into here too

        $pageInfo = new Types\PageInfo(
            hasPreviousPage: false,
            hasNextPage: false,
            startCursor: null,
            endCursor: null,
        );

        $connection = new PublisherRecsConnection();
        $connection->setEdges($edges);
        $connection->setPageInfo($pageInfo);

        return new PublisherRecsEdge($connection, $cursor);
    }
}
