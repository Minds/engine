<?php
namespace Minds\Core\Feeds\GraphQL;

use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\FeedNotices;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\Recommendations\Algorithms\SuggestedChannels\SuggestedChannelsRecommendationsAlgorithm;
use Minds\Core\Recommendations\Algorithms\SuggestedGroups\SuggestedGroupsRecommendationsAlgorithm;
use Minds\Core\Recommendations\Injectors\BoostSuggestionInjector;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(Controllers\NewsfeedController::class, function (Di $di) {
            return new Controllers\NewsfeedController(
                feedsManager: $di->get(FeedsManager::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                feedNoticesManager: $di->get(FeedNotices\Manager::class),
                boostManager: $di->get(BoostManager::class),
                suggestedChannelsRecommendationsAlgorithm: new SuggestedChannelsRecommendationsAlgorithm(),
                boostSuggestionInjector: $di->get(BoostSuggestionInjector::class),
                suggestedGroupsRecommendationsAlgorithm: new SuggestedGroupsRecommendationsAlgorithm(),
                experimentsManager: $di->get('Experiments\Manager'),
                votesManager: $di->get('Votes\Manager'),
                tagRecommendationsManager: $di->get(TagRecommendations\Manager::class)
            );
        });

        $this->di->bind(TagRecommendations\Manager::class, function (Di $di) {
            return new TagRecommendations\Manager();
        });
    }
}
