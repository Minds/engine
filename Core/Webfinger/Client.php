<?php
namespace Minds\Core\Webfinger;

use Minds\Core\Config\Config;
use GuzzleHttp;
use Psr\Http\Message\ResponseInterface;

class Client
{
    public function __construct(
        protected  GuzzleHttp\Client $httpClient,
        protected Config $config
    )
    {
    }
    
    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $body = []): ResponseInterface
    {
        $json = $this->httpClient->request($method, $url, [
                    'headers' => [
                        'Content-Type' => 'application/activity+json',
                        'Accept' => 'application/jrd+json, application/json',
                    ],
                    'json' => $body,
                ]);
       
        return $json;
    }

}
