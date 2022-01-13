<?php

namespace Minds\Core\Blockchain\Services\Web3Services;

use Minds\Core\Blockchain\Services\MindsWeb3Service;
use Minds\Core\Di\Di;
use Minds\Exceptions\ServerErrorException;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Core\Security\RateLimits\RateLimitExceededException;

/**
 * Service for communicating with Minds Web3 Service on SKALE network.
 */
class MindsSKALEWeb3Service extends MindsWeb3Service
{
    /**
     * MindsSKALEWeb3Service constructor.
     * @param FeaturesManager|null $featuresManager
     */
    public function __construct(
        protected ?FeaturesManager $featuresManager = null
    ) {
        parent::__construct();

        $this->featuresManager = $featuresManager ?? Di::_()->get('Features\Manager');

        $xpub = $this->config->get('blockchain')['skale']['wallet']['wallet_address'] ?? false;
        $xpriv = $this->config->get('blockchain')['skale']['wallet']['wallet_pkey'] ?? false;
        $network = $this->config->get('blockchain')['skale']['testnet'] ? 'skaleTestnet' : 'skale';

        $this->setWalletPrivateKey($xpriv)
            ->setWalletPublicKey($xpub)
            ->setEthereumNetwork($network);
    }
    
    /**
     * Request skETH from Minds SKALE faucet.
     * @param string $address - requester address.
     * @throws ServerErrorException - if there is an error.
     * @return string - tx hash.
     */
    public function requestFromSKETHFaucet(string $address): string
    {
        if (!$this->featuresManager->has('skale-faucet')) {
            throw new ServerErrorException('SKALE faucet is not enabled');
        }

        $endpoint = $this->getBaseUrl().'skale/faucet';
        
        $response = $this->httpClient->request('POST', $endpoint, [
            'headers' => $this->buildHeaders(),
            'json' => [
                'requester' => $address
            ]
        ]);

        // reset state since we are forcing network / wallet.
        $this->reset();

        $responseData = json_decode($response->getBody()->getContents(), true);

        if ($responseData['status'] === 200) {
            return $responseData['data'];
        }

        if ($responseData['status'] === 429) {
            throw new RateLimitExceededException();
        }

        throw new ServerErrorException($responseData['message']);
    }
}
