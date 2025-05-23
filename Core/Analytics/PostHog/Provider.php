<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\PostHog;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;
use PostHog\Client as PostHogClient;
use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Analytics\PostHog\Controllers\PostHogGqlController;
use Minds\Core\Data\cache\Cassandra;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(PostHogConfig::class, fn (Di $di) => new PostHogConfig($di->get(Config::class)));

        $this->di->bind(PostHogService::class, function (Di $di): PostHogService {
            /** @var PostHogConfig */
            $postHogConfig = $di->get(PostHogConfig::class);
            return new PostHogService(
                postHogClient: new PostHogClient(
                    apiKey: $postHogConfig->getApiKey(),
                    options: [
                        'host' => $postHogConfig->getHost(),
                    ],
                    personalAPIKey: $postHogConfig->getPersonalApiKey(),
                    loadFeatureFlags: false,
                ),
                postHogConfig: $postHogConfig,
                cache: $di->get(Cassandra::class),
                config: $di->get(Config::class),
            );
        });

        $this->di->bind('PostHogHttpClient', function (Di $di): GuzzleClient {
            /** @var PostHogConfig */
            $postHogConfig = $di->get(PostHogConfig::class);
            
            return new GuzzleClient([
                'base_uri' => "https://{$postHogConfig->getHost()}/",
                'headers' => [
                    'Authorization' => 'Bearer ' . $postHogConfig->getPersonalApiKey(),
                ],
            ]);
        });

        $this->di->bind(PostHogPersonService::class, function (Di $di): PostHogPersonService {
            /** @var PostHogConfig */
            $postHogConfig = $di->get(PostHogConfig::class);
            
            /** @var GuzzleClient */
            $httpClient = $di->get('PostHogHttpClient');

            return new PostHogPersonService(
                postHogConfig: $postHogConfig,
                httpClient: $httpClient,
            );
        });

        $this->di->bind(PostHogGqlController::class, function (Di $di): PostHogGqlController {
            return new PostHogGqlController(
                postHogPersonService: $di->get(PostHogPersonService::class),
            );
        });

        $this->di->bind(PostHogQueryService::class, fn (Di $di) => new PostHogQueryService(
            postHogConfig: $di->get(PostHogConfig::class),
            httpClient: $di->get('PostHogHttpClient'),
            config: $di->get(Config::class)
        ));
    }
}
