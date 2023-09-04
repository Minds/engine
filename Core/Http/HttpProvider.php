<?php
/**
 * Minds HTTP Provider
 */

namespace Minds\Core\Http;

use Minds\Core\Di\Provider;
use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Di\Di;
use Minds\Config\Config;

class HttpProvider extends Provider
{
    public function register()
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

        $this->di->bind(GuzzleClient::class, function (Di $di): GuzzleClient {
            /** @var Config */
            $config = $di->get('Config');

            $guzzleConfig = [];

            if (($httpProxy = $config->get('http_proxy'))) {
                $guzzleConfig['proxy'] =  $httpProxy;
            }

            return new GuzzleClient($guzzleConfig);
        });
    }
}
