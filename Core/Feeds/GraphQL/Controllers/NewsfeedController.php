<?php
namespace Minds\Core\Feeds\GraphQL\Controllers;

use AppendIterator;
use GraphQL\Error\UserError;
use Iterator;
use Minds\Common\Access;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\GraphQL\Types\BoostEdge;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\FeedNotices;
use Minds\Core\FeedNotices\GraphQL\Types\FeedNoticeEdge;
use Minds\Core\FeedNotices\Notices\NoGroupsNotice;
use Minds\Core\Feeds\Elastic\V2\Enums\SeenEntitiesFilterStrategyEnum;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Core\Feeds\GraphQL\Enums\NewsfeedAlgorithmsEnum;
use Minds\Core\Feeds\GraphQL\Types\ActivityEdge;
use Minds\Core\Feeds\GraphQL\Types\ActivityNode;
use Minds\Core\Feeds\GraphQL\Types\FeedHighlightsConnection;
use Minds\Core\Feeds\GraphQL\Types\FeedHighlightsEdge;
use Minds\Core\Feeds\GraphQL\Types\NewsfeedConnection;
use Minds\Core\Feeds\GraphQL\Types\PublisherRecsConnection;
use Minds\Core\Feeds\GraphQL\Types\PublisherRecsEdge;
use Minds\Core\Feeds\GraphQL\Types\UserEdge;
use Minds\Core\GraphQL\Types;
use Minds\Core\Groups\V2\GraphQL\Types\GroupEdge;
use Minds\Core\Recommendations\Algorithms\SuggestedChannels\SuggestedChannelsRecommendationsAlgorithm;
use Minds\Core\Recommendations\Algorithms\SuggestedGroups\SuggestedGroupsRecommendationsAlgorithm;
use Minds\Core\Recommendations\Injectors\BoostSuggestionInjector;
use Minds\Core\Search\Enums\SearchMediaTypeEnum;
use Minds\Core\Votes;
use Minds\Entities\Activity;
use Minds\Entities\User;
use NoRewindIterator;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
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
        protected SuggestedGroupsRecommendationsAlgorithm $suggestedGroupsRecommendationsAlgorithm,
        protected ExperimentsManager $experimentsManager,
        protected Votes\Manager $votesManager,
    ) {
    }

    /**
     * Track when we've shown explicit vote buttons
     * to make sure we don't show them too infrequently
     */
    protected int|null $lastIndexWithExplicitVotes = null;

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
        #[InjectUser] ?User $loggedInUser = null,
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

        $hasMore = false;

        // $loggedInUser =  Session::getLoggedInUser();

        if (!$loggedInUser) {
            throw new UserError("You must be logged in", 403);
        }

        $edges = [];

        /**
         * @var Iterator<Activity> $activities
         */
        $activities = match ($algorithm) {
            NewsfeedAlgorithmsEnum::LATEST => $this->feedsManager->getLatest(
                queryOpts: new QueryOpts(
                    user: $loggedInUser,
                    limit: $limit,
                    onlySubscribed: true,
                    accessId: Access::PUBLIC,
                ),
                loadAfter: $loadAfter,
                loadBefore: $loadBefore,
                hasMore: $hasMore,
            ),
            NewsfeedAlgorithmsEnum::GROUPS => $this->feedsManager->getLatest(
                queryOpts: new QueryOpts(
                    user: $loggedInUser,
                    limit: $limit,
                    onlyGroups: true,
                ),
                loadAfter: $loadAfter,
                loadBefore: $loadBefore,
                hasMore: $hasMore,
            ),
            NewsfeedAlgorithmsEnum::TOP => $this->feedsManager->getTop(
                queryOpts: new QueryOpts(
                    user: $loggedInUser,
                    limit: $limit,
                    onlySubscribed: true,
                    accessId: Access::PUBLIC,
                ),
                loadAfter: $loadAfter,
                loadBefore: $loadBefore,
                hasMore: $hasMore,
            ),
            NewsfeedAlgorithmsEnum::FORYOU => $this->feedsManager->getClusteredRecs(
                user: $loggedInUser,
                limit: $limit,
                loadAfter: $loadAfter,
                loadBefore: $loadBefore,
                hasMore: $hasMore,
            ),
            default => throw new UserError("Invalid algorithm supplied")
        };

        if ($algorithm === NewsfeedAlgorithmsEnum::FORYOU && !$after && $this->experimentsManager->setUser($loggedInUser)->isOn('minds-4169-for-you-top-posts-injection')) {
            $topQueryOpts = new QueryOpts(
                limit: 1, // TODO: swap with configurable value
                query: "",
                accessId: Access::PUBLIC,
                mediaTypeEnum: SearchMediaTypeEnum::toMediaTypeEnum(SearchMediaTypeEnum::ALL),
                nsfw: [],
                seenEntitiesFilterStrategy: SeenEntitiesFilterStrategyEnum::DEMOTE,
            );

            /**
             * @var Iterator<Activity> $topResultActivities
             */
            $topResultActivities = $this->feedsManager->getTop(
                queryOpts: $topQueryOpts,
            );

            $mergeIterator = new AppendIterator();
            $mergeIterator->append(new NoRewindIterator($topResultActivities));
            $mergeIterator->append(new NoRewindIterator($activities));

            $activities = $mergeIterator;
        }

        // Build the boosts
        $isBoostRotatorRemovedExperimentOn = $this->experimentsManager->isOn('minds-4105-remove-rotator');
        $boosts = $this->buildBoosts(
            loggedInUser: $loggedInUser,
            limit: $isBoostRotatorRemovedExperimentOn ? 3 : 1,
        );

        // Reset the explicit vote counter
        $this->lastIndexWithExplicitVotes = null;

        foreach ($activities as $i => $activity) {
            $cursor = $loadAfter;

            // Do not return more than the limit
            if (count($edges) >= $limit) {
                break;
            }

            if (
                (
                    $this->isForYouTopExperimentActive($loggedInUser, $algorithm, $after) &&
                    $i === 1
                )
                ||
                (
                    !$this->isForYouTopExperimentActive($loggedInUser, $algorithm, $after) &&
                    $i === 0
                )
            ) { // Priority notice is always at the top
                $priorityNotices = $this->buildInFeedNotices(
                    loggedInUser: $loggedInUser,
                    cursor: $cursor,
                    inFeedNoticesDelivered: $inFeedNoticesDelivered,
                    algorithm: $algorithm,
                    location: ($after ||$before) ? 'inline' : 'top',
                    limit: 1
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
                    cursor: $cursor,
                    inFeedNoticesDelivered: $inFeedNoticesDelivered,
                    algorithm: $algorithm,
                    location: 'inline',
                    limit: 1
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
                $this->isExplicitVotesExperimentOn() && $this->userHasVoted($activity, $loggedInUser, Votes\Enums\VoteDirectionEnum::DOWN)
            ) {
                continue;
            }

            $showExplicitVoteButtons = $this->showExplicitVoteButtons($activity, $loggedInUser, $i);

            $edges[] = new ActivityEdge($activity, $cursor ?? "", $showExplicitVoteButtons);
        }

        if (empty($edges)) {
            // If the group feed is empty
            if ($algorithm === NewsfeedAlgorithmsEnum::GROUPS) {
                // Show the no groups notice
                $noGroupNotice = $this->buildInFeedNotices(
                    loggedInUser: $loggedInUser,
                    cursor: '',
                    inFeedNoticesDelivered: $inFeedNoticesDelivered,
                    algorithm: $algorithm,
                    location: ($after ||$before) ? 'inline' : 'top',
                    limit: 1
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
            hasNextPage: $hasMore, // Always will be newer data on latest or if we are paging forward
            hasPreviousPage: $algorithm === NewsfeedAlgorithmsEnum::LATEST || ($after && $loadBefore) ? true : false,
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
        if (!$this->boostManager->shouldShowBoosts($loggedInUser)) {
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

    private function isForYouTopExperimentActive(User $loggedInUser, NewsfeedAlgorithmsEnum $algorithm, ?string $after): bool
    {
        return
            $algorithm === NewsfeedAlgorithmsEnum::FORYOU &&
            !$after &&
            $this->experimentsManager->setUser($loggedInUser)->isOn('minds-4169-for-you-top-posts-injection');
    }

    /**
     * Will attempt to build feed highlights, if there any available
     * @return FeedHighlightsEdge|null
     */
    protected function buildFeedHighlights(User $loggedInUser, string $cursor): ?FeedHighlightsEdge
    {
        $activities = $this->feedsManager->getTop(
            queryOpts: new QueryOpts(
                user: $loggedInUser,
                limit: 3,
                onlySubscribed: true,
                seenEntitiesFilterStrategy: SeenEntitiesFilterStrategyEnum::EXCLUDE,
            ),
            hasMore: $hasMore,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
        );

        $edges = [];

        foreach ($activities as $activity) {
            // Don't show downvoted activities
            if (
                $this->isExplicitVotesExperimentOn() && $this->userHasVoted($activity, $loggedInUser, Votes\Enums\VoteDirectionEnum::DOWN)
            ) {
                continue;
            }

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
        $result = $this->suggestedGroupsRecommendationsAlgorithm
            ->setUser($loggedInUser)
            ->getRecommendations([
                'limit' => 3
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
     * Show explicit vote buttons every 4 activities
     * when the experiment is on
     * and the user isn't the post owner
     * and the user hasn't voted up already
     * (assumes we've already checked for downvotes)
     * @param Activity $activity
     * @param User $loggedInUser
     * @param int $i - current index in the list of activities
     */
    protected function showExplicitVoteButtons(Activity $activity, User $loggedInUser, int $i): bool
    {
        $isPostOwner = $loggedInUser->getGuid() === $activity->getOwnerGuid();

        $hasUpvoted = $this->userHasVoted($activity, $loggedInUser, Votes\Enums\VoteDirectionEnum::UP);

        $show = ($i === 0 || $i - $this->lastIndexWithExplicitVotes >= 4 || is_null($this->lastIndexWithExplicitVotes)) && $this->isExplicitVotesExperimentOn() && !$isPostOwner && !$hasUpvoted;

        if ($show) {
            $this->lastIndexWithExplicitVotes = $i;
        }

        return $show;
    }

    /**
     * Helper function to determine if current logged in user has
     * voted on the post
     * @param Activity $activity
     * @param User $loggedInUser
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

    /**
     * Whether experiment is on.
     * @return bool true if experiment is on.
     */
    protected function isExplicitVotesExperimentOn(): bool
    {
        return $this->experimentsManager->isOn('minds-4175-explicit-votes');
    }
}
