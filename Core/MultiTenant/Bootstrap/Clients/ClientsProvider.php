<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Clients;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\Bootstrap\Clients\GoogleFaviconClient;
use Minds\Core\MultiTenant\Bootstrap\Clients\JinaClient;
use OpenAI\Client as OpenAIClient;
use OpenAI;
use GuzzleHttp\Client as GuzzleClient;

class ClientsProvider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            OpenAIClient::class,
            function (Di $di): OpenAIClient {
                $openAiApiKey = $di->get(Config::class)->get('open_ai')['api_key'] ?? null;
                return OpenAI::client($openAiApiKey);
            }
        );

        $this->di->bind(
            JinaClient::class,
            function (Di $di): JinaClient {
                return new JinaClient(
                    guzzleClient: new GuzzleClient(),
                    config: $di->get(Config::class)
                );
            }
        );

        $this->di->bind(
            ScreenshotOneClient::class,
            function (Di $di): ScreenshotOneClient {
                return new ScreenshotOneClient(
                    guzzleClient: new GuzzleClient(),
                    config: $di->get(Config::class)
                );
            }
        );

        $this->di->bind(
            GoogleFaviconClient::class,
            function (Di $di): GoogleFaviconClient {
                return new GoogleFaviconClient(
                    guzzleClient: new GuzzleClient(),
                    config: $di->get(Config::class)
                );
            }
        );
    }
}
