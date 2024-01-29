<?php
namespace Minds\Core\Security\Vault;

use Minds\Core\Config\Config;
use GuzzleHttp;
use Psr\Http\Message\ResponseInterface;

class Client
{
    protected string $cachedAuthToken;
    protected int $cachedAuthTokenTs = 0;

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
                'Authorization' => 'Bearer ' . $this->buildAuthToken(),
            ],
            'json' => $body,
        ];

        if (($httpProxy = $this->config->get('http_proxy'))) {
            $opts['proxy'] =  $httpProxy;
        }

        $json = $this->httpClient->request($method, $url, $opts);
       
        return $json;
    }

    /**
     * Returns the auth token
     */
    public function buildAuthToken(): string
    {
        if (($this->config->get('vault')['auth_method'] ?? 'token') === 'token') {
            return $this->config->get('vault')['token'] ?? 'root';
        }

        if ($this->cachedAuthTokenTs > time() - 3600) {
            return $this->cachedAuthToken;
        }

        if ($this->config->get('vault')['auth_method'] !== 'kubernetes') {
            throw new \Exception("Invalid vault auth method provided. Token and Kubernetes are supported");
        }

        $url = rtrim($this->config->get('vault')['url'], '/') . '/v1/auth/kubernetes/login';

        $json = $this->httpClient->request("POST", $url, [
            'verify' => file_get_contents('/var/run/secrets/kubernetes.io/serviceaccount/ca.crt'),
            'json' => [
                'jwt' => file_get_contents('/var/run/secrets/kubernetes.io/serviceaccount/token'),
                'role' => $this->config->get('vault')['auth_role'] ?? null,
            ]
        ]);

        $body = json_decode($json->getBody()->getContents(), true);

        $this->cachedAuthTokenTs = time();
        return $this->cachedAuthToken = $body['auth']['client_token'];
    }

}
