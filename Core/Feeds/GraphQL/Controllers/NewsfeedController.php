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
use Minds\Core\Suggestions\Suggestion;
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
    ) {
    }

    #[Query]
    public function getNewsfeed(
        string $algorithm,
        ?int $first = null,
        ?string $after = null,
        ?int $last = null,
        ?string $before = null,
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

        foreach ($activities as $i => $activity) {
            $cursor = $loadAfter;
    
            // Do not return more than the limit
            if (count($edges) >= $limit) {
                break;
            }

            if ($i === 0) { // Priority notice is always at the top
                $priorityNotices = $this->getInFeedNotices(
                    loggedInUser: $loggedInUser,
                    location: ($after ||$before) ? 'inline' : 'top',
                    limit: 1,
                    cursor: $cursor
                );
                if ($priorityNotices && isset($priorityNotices[0])) {
                    $edges[] = $priorityNotices[0];
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
                $inlineNotice = $this->getInFeedNotices(
                    loggedInUser: $loggedInUser,
                    location: 'inline',
                    limit: 1,
                    cursor: $cursor
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

            if ($i === 3) { // Show a boost in the 3rd slot
                $boosts = $this->boostManager->getBoostFeed(
                    limit: 1,
                    targetStatus: BoostStatus::APPROVED,
                    orderByRanking: true,
                    targetAudience: $loggedInUser->getBoostRating(),
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    castToFeedSyncEntities: false,
                );
                if ($boosts && isset($boosts[0])) {
                    $edges[] = new BoostEdge($boosts[0], $cursor);
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
     * Add in feed notices
     * @return FeedNoticeEntry[]
     */
    protected function getInFeedNotices(User $loggedInUser, string $cursor, string $location = 'inline', int $limit = 1): array
    {
        $edges = [];

        $feedNotices = $this->feedNoticesManager->getNotices($loggedInUser);
        $i = 0;
        foreach ($feedNotices as $feedNotice) {
            try {
                if ($feedNotice->getLocation() !== $location || !$feedNotice->shouldShow($loggedInUser)) {
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

        // Inject a boosted channel
        $result = $this->boostSuggestionInjector->inject(
            response: $result,
            targetUser: $loggedInUser,
            index: 1
        );

        $edges = [ ];

        foreach ($result as $i => $entity) {
            $cursor = base64_encode($i);
            if ($entity instanceof User) {
                $edges[] = new UserEdge($entity, $cursor);
            }
            if ($entity instanceof Suggestion) {
                $edges[] = new BoostEdge($entity->getEntity()->boost, $cursor);
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
