<?php
/**
 * Service for communicating with Minds Web3 Service.
 * Is capable of dispatching transactions, getting dispatches, getting information on transactions,
 * signing transactions, encoding parameters etc.
 */
namespace Minds\Core\Blockchain\Services\Web3Services;

use GuzzleHttp;
use GuzzleHttp\ClientInterface;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;

class MindsWeb3Service
{
    /** @var string Ethereum network */
    protected $ethereumNetwork = null;

    /** @var string Wallet private key */
    protected $walletPrivateKey = null;
    
    /** @var string Wallet public key */
    protected $walletPublicKey = null;

    public function __construct(
        public ?ClientInterface $httpClient = null,
        public ?Logger $logger = null,
        public ?Config $config = null,
    ) {
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Retrieve encoded function data from the service.
     * @param string $functionSignature - function signature e.g. balanceOf(address).
     * @param array $params - function parameters to encode.
     * @return string - encoded function data.
     * @throws ServerErrorException - if there is an error.
     */
    public function getEncodedFunctionData(string $functionSignature, array $params): string
    {
        $endpoint = $this->getBaseUrl().'tx/encodeFunctionData';

        $response = $this->httpClient->request('POST', $endpoint, [
            'headers' => $this->buildHeaders(),
            'json' => [
                'functionSignature' => $functionSignature,
                'params' => $params
            ]
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        if ($responseData['status'] === 200 && $responseData['data']) {
            return $responseData['data'];
        }

        throw new ServerErrorException(
            $responseData['message'] ??
            'An error occurred getting encoded function data: '.$responseData['message']['reason']
        );
    }

    /**
     * Retrieve encoded transaction data from the service.
     * @param string $transaction - transaction data as array.
     * @return string - encoded transaction data.
     * @throws ServerErrorException - if there is an error.
     */
    public function signTransaction(array $transaction): string
    {
        $endpoint = $this->getBaseUrl().'sign/tx';

        $response = $this->httpClient->request('POST', $endpoint, [
            'headers' => $this->buildHeaders(),
            'json' => $transaction
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        if ($responseData['status'] === 200 && $responseData['data']) {
            return $responseData['data'];
        }

        throw new ServerErrorException('An error occurred signing the transaction: '. $responseData['message']);
    }

    /**
     * Dispatch a request to complete / approve withdraw .
     * @param string $address - The requesting address.
     * @param string $userGuid - GUID of the user we are requesting for.
     * @param string $gas - The amount of gas deposited.
     * @param string $amount - The amount of tokens being approved for withdrawal.
     * @return string - tx hash.
     * @throws ServerErrorException - if there is an error.
     */
    public function withdraw(string $address, string $userGuid, string $gas, string $amount): string
    {
        $endpoint = $this->getBaseUrl().'withdraw/complete';
        
        $response = $this->httpClient->request('POST', $endpoint, [
            'headers' => $this->buildHeaders(),
            'json' => [
                'requester' => $address,
                'user_guid' => $userGuid,
                'gas' => $gas,
                'amount' => $amount
            ]
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        if ($responseData['status'] === 200) {
            return $responseData['data'];
        }

        throw new ServerErrorException($responseData['message']);
    }

    /**
     * Set the Ethereum network we are requesting on.
     * @param string $ethereumNetwork - Ethereum network.
     * @return self
     */
    public function setEthereumNetwork(string $ethereumNetwork): self
    {
        $this->ethereumNetwork = $ethereumNetwork;
        return $this;
    }

    /**
     * Sets the wallet private key we are requesting with
     * @param string $walletPrivateKey - Wallet private key.
     * @return self
     */
    public function setWalletPrivateKey(string $walletPrivateKey): self
    {
        $this->walletPrivateKey = $walletPrivateKey;
        return $this;
    }

    /**
     * Sets wallet public key we are requesting with.
     * @param string $walletPublicKey - Wallet public key.
     * @return self
     */
    public function setWalletPublicKey(string $walletPublicKey): self
    {
        $this->walletPublicKey = $walletPublicKey;
        return $this;
    }

    public function checkHealth(): bool
    {
        $response = $this->httpClient->request('GET', $this->getBaseUrl(), [
            'headers' => $this->buildHeaders()
        ]);
        $responseData = json_decode($response->getBody()->getContents(), true);
        return $responseData['status'] && $responseData['status'] === 200;
    }

    /**
     * Build headers for request
     * @param boolean $authenticate - should authenticate.
     * @return array - headers.
     */
    private function buildHeaders(bool $authenticate = true): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-ETH-NETWORK' => $this->getEthereumNetworkName()
        ];

        if ($authenticate) {
            $headers['X-AUTH-KEY'] = $this->getEncryptedWalletPrivateKey();
            $headers['X-WALLET-ADDRESS'] = $this->getWalletPublicKey();
        }

        return $headers;
    }

    /**
     * Get's the public key of a wallet.
     * @return string public key.
     */
    private function getWalletPublicKey($walletName = 'withdraw'): string
    {
        $xpub = $this->config->get('blockchain')['contracts'][$walletName]['wallet_address'] ?? false;
        if (!$xpub) {
            throw new ServerErrorException('No wallet address is set');
        }
        return $xpub;
    }

    /**
     * Get's the encoded and encrypted private key of a wallet.
     * @return string encoded / encrypted private key.
     */
    private function getEncryptedWalletPrivateKey($walletName = 'withdraw'): string
    {
        $xpriv = $this->getUnencryptedWalletPrivateKey($walletName);
        $key = $this->getEncryptionKey();
        return base64_encode(hash_hmac('SHA256', $xpriv, $key));
    }

    /**
     * Gets unencrypted private key of a wallet.
     * @return string unencrypted private key of a wallet.
     */
    private function getUnencryptedWalletPrivateKey($walletName = 'withdraw'): string
    {
        $xpriv = $this->config->get('blockchain')['contracts'][$walletName]['wallet_pkey'] ?? false;
        if (!$xpriv) {
            throw new ServerErrorException('No unencrypted wallet private key set');
        }
        return $xpriv;
    }

    /**
     * Gets encryption key.
     * @return string key used for encryption.
     */
    private function getEncryptionKey(): string
    {
        $key = $this->config->get('blockchain')['web3_service']['wallet_encryption_key'];
        if (!$key) {
            throw new ServerErrorException('No encryption key set');
        }
        return $key;
    }

    /**
     * Gets name of ethereum network to use.
     * @return string name of ethereum network.
     */
    private function getEthereumNetworkName(): string
    {
        if ($this->ethereumNetwork) {
            return $this->ethereumNetwork;
        }

        switch ($this->config->get('blockchain')['client_network']) {
            case 4:
                return 'rinkeby';
            default:
                return 'mainnet';
        }
    }

    /**
     * Gets base url for service.
     * @return string base url for service.
     */
    private function getBaseUrl(): string
    {
        $baseUrl = $this->config->get('blockchain')['web3_service']['base_url'] ?? false;
        if (!$baseUrl) {
            throw new ServerErrorException('No Base URL is set for web3 service');
        }
        return $baseUrl;
    }
}
