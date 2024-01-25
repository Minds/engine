<?php
namespace Minds\Core\Security\Vault;

use Minds\Core\Config\Config;
use GuzzleHttp;
use Psr\Http\Message\ResponseInterface;

class Client
{
    public function __construct(
        protected  GuzzleHttp\Client $httpClient,
        protected Config $config
    ) {
    }
    
    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return ResponseInterface
     */
    public function request(string $method, string $endpoint, array $body = []): ResponseInterface
    {
        $url = rtrim($this->config->get('vault')['url'], '/') . '/v1/' . ltrim($endpoint, '/');

        $opts = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->get('vault')['token'],
            ],
            'json' => $body,
        ];

        if (($httpProxy = $this->config->get('http_proxy'))) {
            $opts['proxy'] =  $httpProxy;
        }

        $json = $this->httpClient->request($method, $url, $opts);
       
        return $json;
    }

}
