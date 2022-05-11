<?php
namespace Minds\Core\DID\UniResolver;

use Minds\Core\Di\Di;
use Minds\Core\Http;
use Minds\Core\Config\Config;
use Minds\Exceptions\UserErrorException;

class Client
{
    /** @var Http\Curl\Json\Client */
    protected $http;

    /** @var Config */
    protected $config;

    /** @var string */
    protected $uniResolverBaseUrl;

    public function __construct($http = null, ?Config $config = null)
    {
        $this->http = $http ?: Di::_()->get('Http\Json');
        $this->config = $config ?? Di::_()->get('Config');
        $didConfig = $this->config->get('did');
        $this->uniResolverBaseUrl = $didConfig['uniresolver']['base_url'] ?? 'https://dev.uniresolver.io/';
    }

    /**
     * Requests query and validates response
     * @param string $query
     * @param array $variables
     * @return array
     */
    public function request($uri): array
    {
        $response = $this->http->get(
            rtrim($this->uniResolverBaseUrl, '/') . '/' . ltrim($uri, '/'),
            [
                'headers' => [
                    'Content-Type: application/json'
                ]
            ]
        );

        if (!$response) {
            throw new UserErrorException("Invalid response");
        }

        return $response;
    }
}
