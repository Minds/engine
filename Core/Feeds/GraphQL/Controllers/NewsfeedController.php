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
use Minds\Core\Groups\V2\GraphQL\Types\GroupEdge;
use Minds\Core\Recommendations\Algorithms\SuggestedChannels\SuggestedChannelsRecommendationsAlgorithm;
use Minds\Core\Recommendations\Injectors\BoostSuggestionInjector;
use Minds\Core\Suggestions\Manager as SuggestionsManager;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Feeds\Elastic\V2\Enums\SeenEntitiesFilterStrategyEnum;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Query;
use Minds\Core\FeedNotices\Notices\NoGroupsNotice;
use Minds\Core\Feeds\GraphQL\Enums\NewsfeedAlgorithmsEnum;
use Minds\Core\Di\Di;
use Minds\Core\Votes;
use Minds\Entities\Activity;

class NewsfeedController
{
    public function __construct(
        protected FeedsManager $feedsManager,
        protected EntitiesBuilder $entitiesBuilder,
        protected FeedNotices\Manager $feedNoticesManager,
        protected BoostManager $boostManager,
        protected SuggestedChannelsRecommendationsAlgorithm $suggestedChannelsRecommendationsAlgorithm,
        protected BoostSuggestionInjector $boostSuggestionInjector,
        protected SuggestionsManager $suggestionsManager,
        protected ExperimentsManager $experimentsManager,
        protected ?Votes\Manager $votesManager = null,
    ) {
        $this->votesManager ??= Di::_()->get('Votes\Manager');
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
        /**
         * Ideally we would use an enum in the function, but Graphql is not playing nice.
         */
        try {
            $algorithm = NewsfeedAlgorithmsEnum::from($algorithm);
        } catch (\ValueError) {
            throw new UserError("Invalid algorithm provided");
        }

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
            case NewsfeedAlgorithmsEnum::LATEST:
                $activities = $this->feedsManager->getLatestSubscribed(
                    user: $loggedInUser,
                    limit: $limit,
                    hasMore: $hasMore,
                    loadAfter: $loadAfter,
                    loadBefore: $loadBefore,
                );
                break;
            case NewsfeedAlgorithmsEnum::GROUPS:
                $activities = $this->feedsManager->getLatestGroups(
                    user: $loggedInUser,
                    limit: $limit,
                    hasMore: $hasMore,
                    loadAfter: $loadAfter,
                    loadBefore: $loadBefore,
                );
                break;
            case NewsfeedAlgorithmsEnum::TOP:
                $activities = $this->feedsManager->getTopSubscribed(
                    user: $loggedInUser,
                    limit: $limit,
                    hasMore: $hasMore,
                    loadAfter: $loadAfter,
                    loadBefore: $loadBefore,
                );
                break;
            case NewsfeedAlgorithmsEnum::FORYOU:
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

        // Get explicit vote experiment status
        $isExplicitVotesExperimentOn = $this->experimentsManager->isOn('minds-4175-explicit-votes');
        $isExplicitVotesExperimentOn = true; // ojm remove!!!


        // Build the boosts
        $isBoostRotatorRemovedExperimentOn = $this->experimentsManager->isOn('minds-4105-remove-rotator');
        $boosts = $this->buildBoosts(
            loggedInUser: $loggedInUser,
            limit: $isBoostRotatorRemovedExperimentOn ? 3 : 1,
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
                    inFeedNoticesDelivered: $inFeedNoticesDelivered,
                    algorithm: $algorithm
                );
                if ($priorityNotices && isset($priorityNotices[0])) {
                    $edges[] = $priorityNotices[0];
                    $inFeedNoticesDelivered[] = $priorityNotices[0]->getNode()->getKey();
                }
            }

            /**
             * Show top highlights on the first page load
             */
            if ($i === 6 && $algorithm === NewsfeedAlgorithmsEnum::LATEST && !($after || $before)) {
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
                    algorithm: $algorithm
                );
                if ($inlineNotice && isset($inlineNotice[0])) {
                    $edges[] = $inlineNotice[0];
                }
            }
            /**
             * Publisher recommendations
             * On first load, randomly show either channel/group recs
             * Unless we're on the group feed, where we always show group recs
             */
            if ($i === 3 && !($after || $before)) {
                if (mt_rand(0, 1) || $algorithm === NewsfeedAlgorithmsEnum::GROUPS) {
                    $groupRecs = $this->buildGroupRecs($loggedInUser, $cursor, 3);
                    if ($groupRecs) {
                        $edges[] = $groupRecs;
                    }
                } else {
                    $channelRecs = $this->buildChannelRecs($loggedInUser, $cursor);
                    if ($channelRecs) {
                        $edges[] = $channelRecs;
                    }
                }
            }

            /**
             * Show boosts depending on if the experiment to remove the rotator is enabled
             */
            if (
                $isBoostRotatorRemovedExperimentOn &&
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
                !$isBoostRotatorRemovedExperimentOn &&
                count($boosts) &&
                $i == 3
            ) {
                $boost = $boosts[0];
                if ($boost) {
                    $edges[] = new BoostEdge($boost, $cursor);
                }
            }

            /**
             * Don't show the post if it has been explicitly downvoted
             */
            if (
                $isExplicitVotesExperimentOn && $this->userHasVoted($activity, $loggedInUser, Votes\Enums\VoteDirectionEnum::DOWN)
            ) {
                continue;
            }


            /**
             * Show explicit vote buttons every 4 posts
             * when the experiment is on
             * and the user isn't the post owner
             *
             */
            $showExplicitVoteButtons = false;
            if (($i === 0 || $i % 4 === 0) && $isExplicitVotesExperimentOn
                // ojm put this back
                // &&$loggedInUser->getGuid() !== $activity->getOwnerGuid()
            ) {
                $showExplicitVoteButtons = true;
            }

            $edges[] = new ActivityEdge($activity, $cursor, $showExplicitVoteButtons);
        }

        if (empty($edges)) {
            // If the group feed is empty
            if ($algorithm === NewsfeedAlgorithmsEnum::GROUPS) {
                // Show the no groups notice
                $noGroupNotice = $this->buildInFeedNotices(
                    loggedInUser: $loggedInUser,
                    location: ($after ||$before) ? 'inline' : 'top',
                    limit: 1,
                    cursor: '',
                    inFeedNoticesDelivered: $inFeedNoticesDelivered,
                    algorithm: $algorithm
                );
                if ($noGroupNotice && isset($noGroupNotice[0])) {
                    $edges[] = $noGroupNotice[0];
                    $inFeedNoticesDelivered[] = $noGroupNotice[0]->getNode()->getKey();
                }

                // And show suggested groups
                $groupRecs = $this->buildGroupRecs($loggedInUser, $loadBefore, 5);
                if ($groupRecs) {
                    $edges[] = $groupRecs;
                }
            }
        }

        $pageInfo = new Types\PageInfo(
            hasPreviousPage: $algorithm === NewsfeedAlgorithmsEnum::LATEST || ($after && $loadBefore) ? true : false, // Always will be newer data on latest or if we are paging forward
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
        NewsfeedAlgorithmsEnum $algorithm,
        string $location = 'inline',
        int $limit = 1
    ): array {
        $edges = [];

        $feedNotices = $this->feedNoticesManager->getNotices($loggedInUser);
        $i = 0;

        // On the groups tab, the no-groups notice is highest priority (if applicable)
        if ($algorithm === NewsfeedAlgorithmsEnum::GROUPS) {
            $noGroupsNotice = new NoGroupsNotice;

            if (!in_array($noGroupsNotice->getKey(), $inFeedNoticesDelivered, true) && $noGroupsNotice->shouldShow($loggedInUser)) {
                $edges[] = new FeedNoticeEdge($noGroupsNotice, $cursor);

                return $edges;
            }
        }

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
            seenEntitiesStrategy: SeenEntitiesFilterStrategyEnum::EXCLUDE,
            hasMore: $hasMore,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
        );

        $edges = [];

        foreach ($activities as $activity) {
            $cursor = $loadAfter;
            $edges[] = new ActivityEdge($activity, $cursor, false);
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

    /**
     * Builds out group recommendations
     * @return PublisherRecsEdge
     */
    protected function buildGroupRecs(User $loggedInUser, string $cursor, int $listSize = 3): PublisherRecsEdge
    {
        $result = $this->suggestionsManager
            ->setUser($loggedInUser)
            ->setType('group')
            ->getList([
                'limit' => $listSize
            ]);

        $edges = [ ];

        foreach ($result as $i => $suggestion) {
            $cursor = base64_encode($i);
            $entity = $suggestion->getEntity();
            $edges[] = new GroupEdge($entity, $cursor);
        }

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

    /**
     * Helper function to determine if current logged in user has
     * voted on the post
     * @param int $direction - Votes\Enums\VoteDirectionEnum
     * @return bool
     */
    protected function userHasVoted(Activity $activity, User $loggedInUser, int $direction): bool
    {
        if (!$loggedInUser) {
            return false;
        }

        $vote = (new Votes\Vote())
            ->setEntity($activity)
            ->setActor($loggedInUser)
            ->setDirection($direction === Votes\Enums\VoteDirectionEnum::UP ? 'up' : 'down');

        return $this->votesManager->has($vote);
    }
}
