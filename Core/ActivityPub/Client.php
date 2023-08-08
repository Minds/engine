<?php
namespace Minds\Core\ActivityPub;

use Minds\Core\Config\Config;
use GuzzleHttp;
use Minds\Core\Di\Di;
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
                        'Accept' => 'application/activity+json',
                    ],
                    'json' => $body,
                ]);
       
        return $json;
    }

}
