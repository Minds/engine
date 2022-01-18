<?php

namespace Minds\Core\Blockchain\Services\Web3Services;

use Minds\Core\Blockchain\Services\Web3Services\MindsWeb3Service;
use Minds\Exceptions\ServerErrorException;

/**
 * Service for communicating with Ethereum mainnet SKALE contracts.
 */
class MindsSKALEMainnetService extends MindsWeb3Service
{
    /**
     * MindsSKALEMainnetService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $xpub = $this->config->get('blockchain')['contracts']['withdraw']['wallet_address'] ?? false;
        $xpriv = $this->config->get('blockchain')['contracts']['withdraw']['wallet_pkey'] ?? false;

        $this->setWalletPrivateKey($xpriv)
            ->setWalletPublicKey($xpub);
    }
    
    /**
     * Whether user can exit from the community pool.
     * @param string $address - requester address.
     * @throws ServerErrorException - if there is an error.
     * @return bool - true if user has enough funds to exit community pool.
     */
    public function canExit(string $address): bool
    {
        $endpoint = $this->getBaseUrl().'skale/communityPool/canExit';

        $response = $this->httpClient->request('GET', $endpoint, [
            'headers' => $this->buildHeaders(),
            'json' => [
                'requester' => $address
            ],
            'timeout' => 30,
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        if ($responseData['status'] === 200) {
            return $responseData['data'];
        }

        throw new ServerErrorException($responseData['message']);
    }
}
