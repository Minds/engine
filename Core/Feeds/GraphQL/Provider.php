<?php
namespace Minds\Core\Feeds\GraphQL;

use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\FeedNotices;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\Feeds\GraphQL\Repositories\TenantGuestModeFeedMySQLRepository;
use Minds\Core\Feeds\GraphQL\Services\TenantGuestModeFeedsService;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
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
                tagRecommendationsManager: $di->get(TagRecommendations\Manager::class),
                tenantGuestModeFeedsService: $di->get(TenantGuestModeFeedsService::class),
            );
        });

        $this->di->bind(TagRecommendations\Manager::class, function (Di $di) {
            return new TagRecommendations\Manager();
        });

        $this->di->bind(
            alias: TenantGuestModeFeedMySQLRepository::class,
            function: fn (Di $di): TenantGuestModeFeedMySQLRepository => new TenantGuestModeFeedMySQLRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            )
        );

        $this->di->bind(
            alias: TenantGuestModeFeedsService::class,
            function: fn (Di $di): TenantGuestModeFeedsService => new TenantGuestModeFeedsService(
                featuredEntityService: $di->get(FeaturedEntityService::class),
                tenantGuestModeFeedMySQLRepository: $di->get(TenantGuestModeFeedMySQLRepository::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                config: $di->get('Config')
            )
        );
    }
}
