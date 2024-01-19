<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Deployments;

use GuzzleHttp\Client;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\Deployments\Builds\MobilePreviewHandler;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            MobilePreviewHandler::class,
            function (Di $di): MobilePreviewHandler {
                $config = $di->get(Config::class);
                $httpClient = new Client([
                    'base_uri' => $config->get('gitlab')['mobile']['pipeline']['url'],
                ]);
                return new MobilePreviewHandler(
                    httpClient: $httpClient,
                    config: $config
                );
            }
        );
    }
}
