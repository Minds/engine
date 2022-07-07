<?php

namespace Minds\Core\Blockchain\Services;

use Minds\Exceptions\ServerErrorException;

/**
 * SKALE service - extends Ethereum service due to the shared functionality
 * however overrides the RPC endpoints to point at SKALE.
 */
class Skale extends Ethereum
{
    /**
     * Returns the next available RPC endpoint.
     * @return string - best endpoint for RPC to SKALE network.
     * @throws ServerErrorException - if no RPC endpoint is available.
     */
    protected function getBestEndpoint(): string
    {
        $config = $this->config->get()['skale'];

        if (!$config['rpc_endpoints'] && !isset($config['rpc_endpoints'][0])) {
            throw new ServerErrorException('No RPC endpoint available');
        }

        return $config['rpc_endpoints'][0];
    }
}
