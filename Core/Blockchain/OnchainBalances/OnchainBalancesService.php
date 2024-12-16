<?php
namespace Minds\Core\Blockchain\OnchainBalances;

use Minds\Core\Blockchain\Services\BlockFinder;
use Minds\Core\Config\Config;
use Minds\Core\Http\Curl\Json\Client;

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
    public function getAll(int $asOf = null): iterable
    {
        if (!$asOf) {
            $asOf = time() - 300; // 5 mins back
        }

        $blockNumber = $this->blockFinder->getBlockByTimestamp($asOf);

        $gtId = "0x0000000000000000000000000000000000000000";
        while ($gtId) {
            $accounts = $this->fetchAccounts($gtId, $blockNumber);

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
    private function fetchAccounts(string $gtId, int $blockNumber): array
    {
        $query = '
            query($pagingToken: String!, $blockNumber: Int) {
                accounts(
                    where: { 
                        id_gt: $pagingToken 
                    }, 
                    block: { 
                        number: $blockNumber 
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
            'blockNumber' => $blockNumber,
        ];

        $response = $this->request($query, $variables);

        return $response['accounts'];
    }

    /**
     * Make the request
     */
    private function request($query, $variables): array
    {
        $graphqlEndpoint = $this->config->get('blockchain')['graph_url'];

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
