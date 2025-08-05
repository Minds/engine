<?php
namespace Minds\Core\Blockchain\OnchainBalances;

use Minds\Core\Blockchain\Services\BlockFinder;
use Minds\Core\Blockchain\Util;
use Minds\Core\Config\Config;
use Minds\Core\Http\Curl\Json\Client;
use NotImplementedException;

class OnchainBalancesService
{
    public function __construct(
        private Client $http,
        private Config $config,
        private BlockFinder $blockFinder,
    ) {
        
    }

    /**
     * Returns a list of all token holders
     */
    public function getAll(int $chainId = Util::BASE_CHAIN_ID): iterable
    {


        $gtId = "0x0000000000000000000000000000000000000000";
        while ($gtId) {
            $accounts = $this->fetchAccounts($gtId, $chainId);

            if (empty($accounts)) {
                return;
            }

            yield from $accounts;

            $gtId = array_slice($accounts, count($accounts) - 1)[0]['id'];
        }
    }

    /**
     * Function to return the accounts
     * Provide the last account id at $gtId to support pagination
     */
    private function fetchAccounts(string $gtId, int $chainId): array
    {
        $query = '
            query($pagingToken: String!, $blockNumber: Int) {
                accounts(
                    where: { 
                        id_gt: $pagingToken 
                    }
                ) {
                    id
                    balances {
                        amount
                    }
                }
            }
        ';

        $variables = [
            'pagingToken' => strtolower($gtId),
        ];

        $response = $this->request($query, $variables, $chainId);

        return $response['accounts'];
    }

    /**
     * Make the request
     */
    private function request($query, $variables, $chainId): array
    {
        $graphqlEndpoint = match($chainId) {
            1 => $this->config->get('blockchain')['ethereum_graph_url'],
            8453 => $this->config->get('blockchain')['base_graph_url'],
            default => throw new NotImplementedException("Only Base and Ethereum are supported"),
        };

        $response = $this->http->post(
            $graphqlEndpoint,
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
