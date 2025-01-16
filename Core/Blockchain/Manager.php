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

        if ($blockchainConfig['token_addresses'] ?? null) {
            $this->contracts['token'] = Contracts\MindsToken::at($blockchainConfig['token_addresses'][$blockchainConfig['chain_id']]);
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
            'chain_id' => $blockchainConfig['chain_id'] ?? Util::BASE_CHAIN_ID,
            'wallet_address' => $blockchainConfig['wallet_address'] ?? null,
            'boost_wallet_address' => $blockchainConfig['contracts']['boost']['wallet_address'],
            'plus_address' => $blockchainConfig['contracts']['wire']['plus_address'],
            'default_gas_price' => $blockchainConfig['default_gas_price'],
            'server_gas_price' => $blockchainConfig['server_gas_price'],
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
                'wallet_address' => $blockchainConfig['wallet_address'] ?? null,
                'boost_wallet_address' => $blockchainConfig['contracts']['boost']['wallet_address'],
                'plus_address' => $blockchainConfig['contracts']['wire']['plus_address'],
                'default_gas_price' => $blockchainConfig['default_gas_price'],
            ];
        }

        return $result;
    }
}
