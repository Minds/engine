<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\PostHog;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\SharedCache;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;
use PostHog\Client as PostHogClient;

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
                cache: $di->get(SharedCache::class),
            );
        });
    }
}
