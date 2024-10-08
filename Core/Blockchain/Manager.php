<?php

/**
 * Blockchain Manager
 *
 * @author emi
 */

namespace Minds\Core\Blockchain;

use Minds\Core\Di\Di;

class Manager
{
    protected $config;
    protected $contracts = [];
    protected static $infuraProxyEndpoint = 'api/v2/blockchain/proxy/';

    public function __construct($config = null)
    {
        $this->config = $config ?: Di::_()->get('Config');

        $this->initContracts();
    }

    protected function initContracts()
    {
        $blockchainConfig = $this->config->get('blockchain');

        if ($blockchainConfig['token_address'] ?? null) {
            $this->contracts['token'] = Contracts\MindsToken::at($blockchainConfig['token_address']);
        }

        if (isset($blockchainConfig['contracts']['wire']) && isset($blockchainConfig['contracts']['wire']['contract_address'])) {
            $this->contracts['wire'] = Contracts\MindsWire::at($blockchainConfig['contracts']['wire']['contract_address']);
        }

        if (isset($blockchainConfig['contracts']['boost']) && isset($blockchainConfig['contracts']['boost']['contract_address'])) {
            $this->contracts['boost'] = Contracts\MindsBoost::at($blockchainConfig['contracts']['boost']['contract_address']);
        }

        if (isset($blockchainConfig['contracts']['withdraw']) && $blockchainConfig['contracts']['withdraw']['contract_address']) {
            $this->contracts['withdraw'] = Contracts\MindsWithdraw::at($blockchainConfig['contracts']['withdraw']['contract_address']);
        }

        if ($blockchainConfig['token_distribution_event_address'] ?? null) {
            $this->contracts['token_distribution_event'] = Contracts\MindsTokenSaleEvent::at($blockchainConfig['contracts']['token_sale_event']['contract_address']);
        }
    }

    public function getContract($contract)
    {
        if (isset($this->contracts[$contract])) {
            return $this->contracts[$contract];
        }
        return null;
    }

    public function getPublicSettings()
    {
        $blockchainConfig = $this->config->get('blockchain') ?: [];

        return array_merge([
            'network_address' => $this->config->get('site_url') . self::$infuraProxyEndpoint,
            'client_network' => $blockchainConfig['client_network'],
            'wallet_address' => $blockchainConfig['wallet_address'] ?? null,
            'boost_wallet_address' => $blockchainConfig['contracts']['boost']['wallet_address'],
            'token_distribution_event_address' => $blockchainConfig['contracts']['token_sale_event']['contract_address'],
            'rate' => $blockchainConfig['eth_rate'],
            'plus_address' => $blockchainConfig['contracts']['wire']['plus_address'],
            'default_gas_price' => $blockchainConfig['default_gas_price'],
            'server_gas_price' => $blockchainConfig['server_gas_price'],
            'transak' => $blockchainConfig['transak'] ?? [
                'api_key' => '',
                'environment' => 'STAGING',
            ],
            'overrides' => $this->getOverrides(),
            'withdraw_limit' => $blockchainConfig['contracts']['withdraw']['limit'] ?? 1,
        ], $this->contracts);
    }

    public function getOverrides()
    {
        $baseConfig = $this->config->get('blockchain') ?: [];
        $overrides = $this->config->get('blockchain_override') ?: [];
        $result = [];

        foreach ($overrides as $key => $override) {
            $blockchainConfig = array_merge($baseConfig, $override);

            $result[$key] = [
                'network_address' => $blockchainConfig['network_address'] ?? null,
                'client_network' => $blockchainConfig['client_network'],
                'wallet_address' => $blockchainConfig['wallet_address'] ?? null,
                'boost_wallet_address' => $blockchainConfig['contracts']['boost']['wallet_address'],
                'token_distribution_event_address' => $blockchainConfig['contracts']['token_sale_event']['contract_address'],
                'plus_address' => $blockchainConfig['contracts']['wire']['plus_address'],
                'default_gas_price' => $blockchainConfig['default_gas_price'],
            ];
        }

        return $result;
    }
}
