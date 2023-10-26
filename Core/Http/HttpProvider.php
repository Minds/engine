<?php
/**
 * Minds HTTP Provider
 */

namespace Minds\Core\Http;

use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Http\Cloudflare\Client as CloudflareClient;

class HttpProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        /**
         * HTTP bindings
         */
        $this->di->bind('Http', function ($di) {
            return new Curl\Client();
        }, ['useFactory'=>true]);

        $this->di->bind('Http\Json', function ($di) {
            return new Curl\Json\Client();
        }, ['useFactory'=>true]);

        $this->di->bind('Http\JsonRpc', function ($di) {
            return new Curl\JsonRpc\Client();
        }, ['useFactory'=>true]);

        $this->di->bind(
            CloudflareClient::class,
            function(Di $di): CloudflareClient {
                /** @var Config $mindsConfig */
                $mindsConfig = $di->get('Config');

                $httpClient = new GuzzleClient([
                    'base_uri' => $mindsConfig->get('cloudflare')['apex_proxy']['zone_url'],
                    'headers' => [
                        'X-Auth-Email' => $mindsConfig->get('cloudflare')['email'],
                        'X-Auth-Key' => $mindsConfig->get('cloudflare')['api_key'],
                    ],
                ]);

                return new CloudflareClient($httpClient);
            }
        );


        $this->di->bind(GuzzleClient::class, function (Di $di): GuzzleClient {
            /** @var Config $config */
            $config = $di->get('Config');

            $guzzleConfig = [];

            if (($httpProxy = $config->get('http_proxy'))) {
                $guzzleConfig['proxy'] =  $httpProxy;
            }

            return new GuzzleClient($guzzleConfig);
        });
    }
}
