<?php
namespace Minds\Core\Blockchain\UnstoppableDomains;

use Brick\Math\BigDecimal;
use Minds\Core\Di\Di;
use Minds\Core\Http;
use Minds\Core\Blockchain\Services\BlockFinder;
use Minds\Core\Config\Config;
use Minds\Exceptions\ServerErrorException;

class Client
{
    /** @var Http\Curl\Json\Client */
    protected $http;

    /** @var Config */
    protected $config;

    /** @var string */
    protected $graphqlEndpoint = "https://api.thegraph.com/subgraphs/name/uniswap/uniswap-v2";

    public function __construct($http = null, ?Config $config = null)
    {
        $this->http = $http ?: Di::_()->get('Http\Json');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * @param string $id
     * @return string[]
     */
    public function getDomains(string $walletAddress): array
    {
        $res = $this->request("reverse/$walletAddress");
        return [ $res['meta']['domain'] ];
    }

    /**
     * @param string $endpoint
     * @return array
     */
    private function request($endpoint): array
    {
        $response = $this->http->get(
            'https://resolve.unstoppabledomains.com/' . $endpoint,
            [
                'headers' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->getConfig('api_key'),
                ]
            ]
        );

        if (!$response) {
            throw new \Exception("Invalid response");
        }

        return $response;
    }

    /**
     * Returns config variables
     * @param string $key
     * @return mixed
     */
    private function getConfig($key): mixed
    {
        $config = $this->config->get('blockchain')['unstoppable_domains'] ?? [];
        return $config[$key] ?? null;
    }
}
