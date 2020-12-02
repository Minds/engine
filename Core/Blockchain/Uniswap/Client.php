<?php
namespace Minds\Core\Blockchain\Uniswap;

use Minds\Core\Di\Di;
use Minds\Core\Http;
use Brick\Math\BigDecimal;

class Client
{
    /** @var Http\Curl\Json\Client */
    protected $http;

    /** @var string */
    protected $graphqlEndpoint = "https://api.thegraph.com/subgraphs/name/uniswap/uniswap-v2";

    public function __construct($http = null)
    {
        $this->http = $http ?: Di::_()->get('Http\Json');
    }

    /**
     * Returns a user with their liquidity positions
     * @param string $id
     * @return UniswapUserEntity
     */
    public function getUser(string $id): UniswapUserEntity
    {
        $query = '
            query($id: String!) {
                user(id: $id) {
                    id
                    liquidityPositions {
                        id
                        liquidityTokenBalance
                        pair {
                            id
                            totalSupply
                        }
                    }
                    usdSwapped
                }
            }
        ';
        $variables = [
            'id' => strtolower($id),
        ];

        $response = $this->request($query, $variables);

        $uniswapUser = new UniswapUserEntity();
        $uniswapUser->setId($response['user']['id'])
            ->setUsdSwaped($response['user']['usdSwaped']);

        $liquidityPositions = [];

        foreach (($response['user']['liquidityPositions'] ?? []) as $liquidityPosition) {
            $uniswapPair = new UniswapPairEntity();
            $uniswapPair->setId($liquidityPosition['pair']['id'])
                ->setTotalSupply(BigDecimal::of($liquidityPosition['pair']['totalSupply']));
                
            $uniswapLiquidityPostition = new UniswapLiquidityPositionEntity();
            $uniswapLiquidityPostition->setId($liquidityPosition['id'])
                ->setLiquidityTokenBalance(BigDecimal::of($liquidityPosition['liquidityTokenBalance']))
                ->setPair($uniswapPair);
            $liquidityPositions[] = $uniswapLiquidityPostition;
        }

        $uniswapUser->setLiquidityPositions($liquidityPositions);

        return $uniswapUser;
    }

    /**
     * Returns pairs based on an array of ids
     * @param string[] $ids
     * @return UniswapPairEntity[]
     */
    public function getPairs(array $ids = []): array
    {
        $query = '
            query($ids: [String!]) {
                pairs(where: { id_in: $ids }) {
                    id
                    totalSupply
                }
            }
        ';
        $variables = [
            'ids' => array_map(function ($id) {
                return strtolower($id);
            }, $ids),
        ];

        $response = $this->request($query, $variables);

        $pairs = [];

        foreach ($response['pairs'] as $pair) {
            $uniswapPair = new UniswapPairEntity();
            $uniswapPair->setId($pair['id'])
                ->setTotalSupply(BigDecimal::of($pair['totalSupply']));
            $pairs[] = $uniswapPair;
        }

        return $pairs;
    }

    /**
     * Requests query and validates response
     * @param string $query
     * @param array $variables
     * @return array
     */
    private function request($query, $variables): array
    {
        $response = $this->http->post(
            $this->graphqlEndpoint,
            [
                'query' => $query,
                'variables' => $variables,
        ],
            [
            'headers' => [
                'Content-Type: application/json'
            ]
        ]
        );

        if (!$response || !$response['data'] ?? null) {
            throw new \Exception("Invalid response");
        }

        return $response['data'];
    }
}
