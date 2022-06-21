<?php
namespace Minds\Core\Blockchain\Uniswap;

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

    /** @var BlockFinder */
    protected $blockFinder;

    /** @var Config */
    protected $config;

    /** @var string */
    protected $graphqlEndpoint = "https://api.thegraph.com/subgraphs/name/uniswap/uniswap-v2";

    public function __construct($http = null, BlockFinder $blockFinder = null, ?Config $config = null)
    {
        $this->http = $http ?: Di::_()->get('Http\Json');
        $this->blockFinder = $blockFinder ?? Di::_()->get('Blockchain\Services\BlockFinder');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Returns a user with their liquidity positions
     * @param string $id
     * @param int $asOf (optional) - Thie timestamp of the block
     * @return UniswapUserEntity
     */
    public function getUser(string $id, int $asOf = null): UniswapUserEntity
    {
        if (!$asOf) {
            $asOf = time() - 300; // 5 minutes (give blocks time to settle)
        }

        $query = '
            query($id: String!, $blockNumber: Int!) {
                user(id: $id, block: { number: $blockNumber }) {
                    id
                    liquidityPositions {
                        id
                        liquidityTokenBalance
                        pair {
                            id
                            totalSupply
                            reserve0
                            reserve1
                            reserveUSD
                        }
                    }
                    usdSwapped
                }
                mints(where: { to: $id}) {
                    id
                    to
                    amount0
                    amount1
                    amountUSD
                    liquidity
                    pair {
                      id
                      totalSupply
                      reserve0
                      reserve1
                      reserveUSD
                    }
                }
                burns(where: { to: $id}) {
                    id
                    to
                    amount0
                    amount1
                    amountUSD
                    liquidity
                    pair {
                      id
                      totalSupply
                      reserve0
                      reserve1
                      reserveUSD
                    }
                }
            }
        ';
        $variables = [
            'id' => strtolower($id),
            'blockNumber' => $this->blockFinder->getBlockByTimestamp($asOf),
        ];

        $response = $this->request($query, $variables);

        $uniswapUser = new UniswapUserEntity();
        $uniswapUser->setId($response['user']['id'])
            ->setUsdSwapped($response['user']['usdSwaped'] ?? 0);

        // Liquidity Positions

        $uniswapUser->setLiquidityPositions(array_map(function ($liquidityPosition) {
            return UniswapLiquidityPositionEntity::build($liquidityPosition);
        }, $response['user']['liquidityPositions'] ?? []));

        // Mints

        $uniswapUser->setMints(array_map(function ($mint) {
            return UniswapMintEntity::build($mint);
        }, $response['mints']));

        // Burns

        $uniswapUser->setBurns(array_map(function ($mint) {
            return UniswapBurnEntity::build($mint);
        }, $response['burns']));
        
        return $uniswapUser;
    }

    /**
     * Returns pairs based on an array of ids
     * @param string[] $ids
     * @return UniswapPairEntity[]
     */
    public function getPairs(array $ids = [], int $asOf = null): array
    {
        if (!$asOf) {
            $asOf = time() - 300; // 5 minutes (give blocks time to settle)
        }

        $query = '
            query($ids: [String!], $blockNumber: Int!) {
                pairs(where: { id_in: $ids }, block: { number: $blockNumber }) {
                    id
                    totalSupply
                    reserve0
                    reserve1
                    reserveUSD
                    volumeToken0
                    volumeToken1
                    volumeUSD
                    untrackedVolumeUSD
                }
            }
        ';
        $variables = [
            'ids' => array_map(function ($id) {
                return strtolower($id);
            }, $ids),
            'blockNumber' => $this->blockFinder->getBlockByTimestamp($asOf),
        ];

        $response = $this->request($query, $variables);

        $pairs = [];

        foreach ($response['pairs'] as $pair) {
            $pairs[] = UniswapPairEntity::build($pair);
        }

        return $pairs;
    }

    /**
     * Returns mints in descending order
     * TODO: add time params
     * @param array $paidIds
     * @param int $skip - to use when iterating. function calls itself.
     * @return UniswapMintEntity[]
     */
    public function getMintsByPairIds(array $pairIds = [], int $skip = 0): array
    {
        $query = '
            query($ids: [String!], $skip: Int!) {
                mints(where: { pair_in: $ids }, orderBy: timestamp, orderDirection: desc, first: 1000, skip: $skip) {
                    id
                    to
                    amount0
                    amount1
                    amountUSD
                    liquidity
                    pair {
                        id
                        totalSupply
                        reserve0
                        reserve1
                        reserveUSD
                    }
                } 
            }
        ';
        $variables = [
            'ids' => array_map(function ($id) {
                return strtolower($id);
            }, $pairIds),
            'skip' => $skip,
        ];

        $response = $this->request($query, $variables);

        $mints = [];

        foreach ($response['mints'] as $mint) {
            $mints[] = UniswapMintEntity::build($mint);
        }

        if (count($mints) >= 1000) {
            array_push($mints, ...$this->getMintsByPairIds($pairIds, count($mints)));
        }

        return $mints;
    }

    /**
     * Get token prices in USD
     * @param string $tokenAddress
     * @return array
     */
    public function getTokenUsdPrices(string $tokenAddress): array
    {
        $query = '
            query($tokenAddress: String!) {
                bundle(id: "1") {
                    ethPrice
                } 
                token(id: $tokenAddress) {
                    derivedETH
                } 
            }
        ';

        $variables = [
            'tokenAddress' => strtolower($tokenAddress),
        ];

        $response = $this->request($query, $variables);

        $ethUsd = BigDecimal::of($response['bundle']['ethPrice']);

        $ethToken = null;

        if ($response['token'] && $response['token']['derivedETH']) {
            $ethToken = BigDecimal::of($response['token']['derivedETH']);
        } elseif ($this->config->get('development_mode')) {
            // Tokens in development mode are not mainnet, and aren't on Uniswap.
            $ethToken = BigDecimal::of(1);
        }

        $tokenUsd = $ethUsd->multipliedBy($ethToken);

        return [
            'eth' => $ethUsd,
            'token' => $tokenUsd,
        ];
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
