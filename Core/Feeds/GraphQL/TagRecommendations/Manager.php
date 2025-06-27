<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\GraphQL\TagRecommendations;

use Minds\Common\Access;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\V2\Enums\SeenEntitiesFilterStrategyEnum;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Core\Feeds\GraphQL\Types\ActivityEdge;
use Minds\Core\Feeds\GraphQL\Types\FeedExploreTagEdge;
use Minds\Core\Feeds\GraphQL\Types\FeedHeaderEdge;
use Minds\Core\Feeds\GraphQL\Types\PublisherRecsConnection;
use Minds\Core\Feeds\GraphQL\Types\PublisherRecsEdge;
use Minds\Core\GraphQL\Types;
use Minds\Core\Groups\V2\GraphQL\Types\GroupEdge;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Core\Log\Logger;
use Minds\Core\Search\Enums\SearchMediaTypeEnum;
use Minds\Core\Search\Search;
use Minds\Entities\Group;
use Minds\Entities\User;

/**
 * Manager that handles the injection of recommendations based on
 * a given tag, or a random user selected tag if one is not provided.
 */
class Manager
{
    public function __construct(
        protected ?FeedsManager $feedsManager = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Search $search = null,
        protected ?UserHashtagsManager $userHashtagsManager = null,
        protected ?Logger $logger = null
    ) {
        $this->feedsManager ??= Di::_()->get(FeedsManager::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->search ??= Di::_()->get('Search\Search');
        $this->userHashtagsManager ??= Di::_()->get('Hashtags\User\Manager');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Prepend tag recommendation egdes to edges for display in feed.
     * @param array &$edges - array of edges.
     * @param User $user - user to get recommendations for.
     * @param string|null $tag - tag to get recommendations for - if not provided
     * will select a random tag that the user has selected previously.
     * @param string $cursor - cursor to set on the edges.
     * @return array - edges.
     */
    public function prepend(
        array $edges,
        User $user,
        string $tag = null,
        string $cursor = ''
    ): array {
        try {
            $unmodifiedEdges = $edges;

            // if no tag parameter is provided, try to use a random tag.
            if (!$tag) {
                $tag = $this->userHashtagsManager->setUser($user)
                    ->getRandomUserSelectedTag();

                if (!$tag) {
                    return $edges;
                }
            }

            $activities = iterator_to_array($this->feedsManager->getTop(queryOpts: new QueryOpts(
                limit: 3,
                query: $tag,
                accessId: Access::PUBLIC,
                mediaTypeEnum: SearchMediaTypeEnum::toMediaTypeEnum(SearchMediaTypeEnum::ALL),
                nsfw: [],
                seenEntitiesFilterStrategy: SeenEntitiesFilterStrategyEnum::DEMOTE,
            )));

            if (count($activities)) {
                $groupTagRecommendations = $this->buildGroupRecsByTag(
                    tag: $tag,
                    cursor: $cursor,
                    listSize: 4,
                    dismissible: false
                );

                // reverse order as we are prepending one-by-one.
                array_unshift($edges, new FeedHeaderEdge("Based on your interests", $cursor));
                array_unshift($edges, new FeedExploreTagEdge($tag, $cursor));
                if ($groupTagRecommendations) {
                    array_unshift($edges, $groupTagRecommendations);
                }
                foreach (array_reverse($activities) as $activity) {
                    array_unshift($edges, new ActivityEdge($activity, $cursor, false));
                }
                array_unshift($edges, new FeedHeaderEdge("#$tag trending", $cursor));
            }

            return $edges;
        } catch (\Exception $e) {
            $this->logger->error($e);
            $edges = $unmodifiedEdges; // reset and log error.
            return $edges;
        }
    }

    /**
     * Builds out group publisher recommendations edge for a given tag.
     * @return PublisherRecsEdge publisher recs egde.
     */
    protected function buildGroupRecsByTag(string $tag = '', string $cursor = '', int $listSize = 4, bool $dismissible = true): ?PublisherRecsEdge
    {
        $guids = array_map(fn ($doc) => $doc['guid'], $this->search->suggest('group', $tag, $listSize));

        if (count($guids) < 1) {
            return null;
        }

        $groups = array_filter(array_map(fn ($guid) => $this->entitiesBuilder->single($guid), $guids));

        $edges = [ ];
        foreach ($groups as $group) {
            if (!($group instanceof Group)) {
                $this->logger->warning('Constructed non-group entity');
                continue;
            }
            $edges[] = new GroupEdge($group, $cursor);
        }

        $connection = new PublisherRecsConnection();
        $connection->setEdges($edges);
        $connection->setPageInfo(new Types\PageInfo(
            hasPreviousPage: false,
            hasNextPage: false,
            startCursor: null,
            endCursor: null,
        ));
        $connection->setDismissible($dismissible);

        return new PublisherRecsEdge($connection, $cursor);
    }
}
